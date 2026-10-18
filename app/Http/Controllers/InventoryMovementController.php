<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryAdjustmentRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockMovement::with('product', 'user')->latest();
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        return view('inventory.index', [
            'movements' => $query->paginate(20)->withQueryString(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'stock']),
        ]);
    }

    public function store(InventoryAdjustmentRequest $request, StockLedger $ledger): RedirectResponse
    {
        DB::transaction(function () use ($request, $ledger) {
            $product = Product::whereKey($request->validated('product_id'))->lockForUpdate()->firstOrFail();
            $delta = (int) $request->validated('quantity') * ($request->validated('direction') === 'in' ? 1 : -1);
            $ledger->adjust($product, $delta, 'adjustment', null, $request->validated('description'));
        }, 3);

        return back()->with('status', 'Ajuste registrado y existencia actualizada.');
    }
}
