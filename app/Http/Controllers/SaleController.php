<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Services\StockLedger;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sale::with('customer', 'user')->latest();
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($builder) => $builder->where('reference', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('sales.index', ['sales' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('sales.create', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('stock', '>', 0)->orderBy('name')->get(['id', 'sku', 'name', 'sale_price', 'stock']),
            'requestKey' => (string) Str::uuid(),
            'taxRate' => (string) config('inventory.tax_rate', '0.00'),
        ]);
    }

    public function store(SaleRequest $request, StockLedger $ledger): RedirectResponse
    {
        $data = $request->validated();
        $existing = Sale::where('request_key', $data['request_key'])->first();
        if ($existing) {
            abort_unless($existing->user_id === $request->user()->id, 409);

            return redirect()->route('sales.show', $existing);
        }

        if (! empty($data['customer_id']) && ! Customer::whereKey($data['customer_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'Selecciona un cliente activo.']);
        }

        try {
            $sale = DB::transaction(function () use ($request, $data, $ledger) {
                $itemsByProduct = collect($data['items'])->keyBy('product_id');
                $products = Product::whereIn('id', $itemsByProduct->keys())
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $subtotal = 0;

                foreach ($itemsByProduct as $productId => $item) {
                    $product = $products->get($productId);
                    if (! $product || ! $product->is_active) {
                        throw ValidationException::withMessages(['items' => 'Todos los productos deben estar activos.']);
                    }
                    if ($product->stock < (int) $item['quantity']) {
                        throw ValidationException::withMessages(['items' => "Stock insuficiente de {$product->name}. Disponible: {$product->stock}."]);
                    }
                    $subtotal = Money::addToTotal($subtotal, Money::lineTotal($product->sale_price, (int) $item['quantity']));
                }

                $discount = Money::cents($data['discount_amount'] ?? '0');
                if ($discount >= $subtotal) {
                    throw ValidationException::withMessages(['discount_amount' => 'El descuento debe ser menor al subtotal.']);
                }

                $taxRate = (string) config('inventory.tax_rate', '0.00');
                $tax = Money::tax($subtotal - $discount, $taxRate);
                $total = $subtotal - $discount + $tax;
                if ($total > 99999999999999) {
                    throw ValidationException::withMessages(['items' => 'El total supera el máximo permitido.']);
                }
                $sale = Sale::create([
                    'reference' => 'VTA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'request_key' => $data['request_key'],
                    'customer_id' => $data['customer_id'] ?? null,
                    'user_id' => $request->user()->id,
                    'subtotal' => Money::decimal($subtotal),
                    'discount_amount' => Money::decimal($discount),
                    'tax_rate' => $taxRate,
                    'tax_amount' => Money::decimal($tax),
                    'total' => Money::decimal($total),
                    'status' => 'completed',
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($itemsByProduct as $productId => $item) {
                    $product = $products->get($productId);
                    $lineTotal = Money::lineTotal($product->sale_price, (int) $item['quantity']);
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->sale_price,
                        'line_total' => Money::decimal($lineTotal),
                    ]);
                    $ledger->adjust($product, -(int) $item['quantity'], 'sale', $sale, "Venta {$sale->reference}");
                }

                Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $data['payment_method'],
                    'amount' => Money::decimal($total),
                    'user_id' => $request->user()->id,
                ]);

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'sale.completed',
                    'auditable_type' => $sale->getMorphClass(),
                    'auditable_id' => $sale->id,
                    'details' => ['reference' => $sale->reference, 'total' => $sale->total],
                    'ip_address' => $request->ip(),
                ]);

                return $sale;
            }, 3);
        } catch (QueryException $exception) {
            $existing = Sale::where('request_key', $data['request_key'])->first();
            if ($existing && $existing->user_id === $request->user()->id) {
                return redirect()->route('sales.show', $existing);
            }

            throw $exception;
        }

        return redirect()->route('sales.show', $sale)->with('status', 'Venta confirmada y existencias actualizadas.');
    }

    public function show(Sale $sale): View
    {
        return view('sales.show', ['sale' => $sale->load('customer', 'user', 'items', 'payments')]);
    }

    public function cancel(Sale $sale, Request $request, StockLedger $ledger): RedirectResponse
    {
        DB::transaction(function () use ($sale, $request, $ledger) {
            $lockedSale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($lockedSale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Esta venta ya fue cancelada.']);
            }

            $items = $lockedSale->items()->orderBy('product_id')->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (! $product) {
                    throw ValidationException::withMessages(['sale' => "El producto {$item->sku} ya no está disponible."]);
                }
                $ledger->adjust($product, $item->quantity, 'sale_return', $lockedSale, "Devolución {$lockedSale->reference}");
            }

            $lockedSale->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            foreach ($lockedSale->payments as $payment) {
                Payment::create([
                    'sale_id' => $lockedSale->id,
                    'method' => 'refund',
                    'amount' => '-'.$payment->amount,
                    'reference' => $lockedSale->reference,
                    'user_id' => $request->user()->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'sale.cancelled',
                'auditable_type' => $lockedSale->getMorphClass(),
                'auditable_id' => $lockedSale->id,
                'details' => ['reference' => $lockedSale->reference],
                'ip_address' => $request->ip(),
            ]);
        }, 3);

        return back()->with('status', 'Venta cancelada, el stock fue devuelto y el reintegro quedó registrado.');
    }
}
