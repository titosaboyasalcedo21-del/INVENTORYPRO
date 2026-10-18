<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\StockLedger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Purchase::with('supplier', 'user')->latest();
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($builder) => $builder->where('reference', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('purchases.index', ['purchases' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('purchases.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'purchase_price', 'stock']),
            'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $existing = Purchase::where('request_key', $data['request_key'])->first();
        if ($existing) {
            abort_unless($existing->user_id === $request->user()->id, 409);

            return redirect()->route('purchases.show', $existing);
        }

        $supplier = Supplier::whereKey($data['supplier_id'])->where('is_active', true)->exists();
        if (! $supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Selecciona un proveedor activo.']);
        }

        $purchase = DB::transaction(function () use ($request, $data) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(['items' => 'Todos los productos deben estar activos.']);
                }
                $subtotal = Money::addToTotal($subtotal, Money::lineTotal($item['unit_cost'], (int) $item['quantity']));
            }

            $taxRate = (string) config('inventory.tax_rate', '0.00');
            $tax = Money::tax($subtotal, $taxRate);
            if ($subtotal + $tax > 99999999999999) {
                throw ValidationException::withMessages(['items' => 'El total supera el máximo permitido.']);
            }
            $purchase = Purchase::create([
                'reference' => 'CMP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'request_key' => $data['request_key'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => $request->user()->id,
                'subtotal' => Money::decimal($subtotal),
                'tax_amount' => Money::decimal($tax),
                'total' => Money::decimal($subtotal + $tax),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $line = Money::lineTotal($item['unit_cost'], (int) $item['quantity']);
                $purchase->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_cost' => Money::decimal(Money::cents($item['unit_cost'])),
                    'line_total' => Money::decimal($line),
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'purchase.created',
                'auditable_type' => $purchase->getMorphClass(),
                'auditable_id' => $purchase->id,
                'details' => ['reference' => $purchase->reference, 'total' => $purchase->total],
                'ip_address' => $request->ip(),
            ]);

            return $purchase;
        }, 3);

        return redirect()->route('purchases.show', $purchase)->with('status', 'Compra creada. Recíbela para actualizar las existencias.');
    }

    public function show(Purchase $purchase): View
    {
        return view('purchases.show', ['purchase' => $purchase->load('supplier', 'user', 'items.product')]);
    }

    public function receive(Purchase $purchase, Request $request, StockLedger $ledger): RedirectResponse
    {
        DB::transaction(function () use ($purchase, $request, $ledger) {
            $lockedPurchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if ($lockedPurchase->status !== 'pending') {
                throw ValidationException::withMessages(['purchase' => 'Esta compra ya fue recibida o cancelada.']);
            }
            $items = $lockedPurchase->items()->orderBy('product_id')->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (! $product) {
                    throw ValidationException::withMessages(['purchase' => "El producto {$item->sku} ya no está disponible."]);
                }
                $ledger->adjust($product, $item->quantity, 'purchase_received', $lockedPurchase, "Recepción {$lockedPurchase->reference}");
                $product->update(['purchase_price' => $item->unit_cost]);
            }

            $lockedPurchase->update(['status' => 'received', 'received_at' => now()]);
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'purchase.received',
                'auditable_type' => $lockedPurchase->getMorphClass(),
                'auditable_id' => $lockedPurchase->id,
                'details' => ['reference' => $lockedPurchase->reference],
                'ip_address' => $request->ip(),
            ]);
        }, 3);

        return back()->with('status', 'Compra recibida. Las existencias se actualizaron una sola vez.');
    }

    public function cancel(Purchase $purchase, Request $request, StockLedger $ledger): RedirectResponse
    {
        DB::transaction(function () use ($purchase, $request, $ledger) {
            $lockedPurchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if ($lockedPurchase->status === 'cancelled') {
                throw ValidationException::withMessages(['purchase' => 'Esta compra ya fue cancelada.']);
            }
            $items = $lockedPurchase->status === 'received'
                ? $lockedPurchase->items()->orderBy('product_id')->get()
                : collect();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (! $product) {
                    throw ValidationException::withMessages(['purchase' => "El producto {$item->sku} ya no está disponible."]);
                }
                $ledger->adjust($product, -$item->quantity, 'purchase_cancelled', $lockedPurchase, "Anulación {$lockedPurchase->reference}");
            }

            $lockedPurchase->update(['status' => 'cancelled']);
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'purchase.cancelled',
                'auditable_type' => $lockedPurchase->getMorphClass(),
                'auditable_id' => $lockedPurchase->id,
                'details' => ['reference' => $lockedPurchase->reference],
                'ip_address' => $request->ip(),
            ]);
        }, 3);

        return back()->with('status', 'Compra cancelada.');
    }
}
