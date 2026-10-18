<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('category')->latest();
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($request->query('stock') === 'low') {
            $query->lowStock();
        } elseif ($request->query('stock') === 'available') {
            $query->whereColumn('stock', '>', 'minimum_stock');
        }

        if ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->query('status') !== 'all') {
            $query->where('is_active', true);
        }

        return view('products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'stockFilter' => (string) $request->query('stock', ''),
            'statusFilter' => (string) $request->query('status', 'active'),
        ]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => null, 'categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('product-images', 'public');
        }

        $product = Product::create($data);
        $this->audit($request, 'product.created', $product, ['sku' => $product->sku]);

        return redirect()->route('products.index')->with('status', 'Producto creado correctamente. Registra su stock inicial en Movimientos.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'product' => $product->load('category'),
            'movements' => $product->movements()->with('user')->latest()->paginate(10),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $oldPath = null;

        if ($request->hasFile('image')) {
            $oldPath = $product->image_path;
            $data['image_path'] = $request->file('image')->store('product-images', 'public');
        }

        $product->update($data);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
        $this->audit($request, 'product.updated', $product, ['sku' => $product->sku]);

        return redirect()->route('products.index')->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);
        $this->audit(request(), $product->is_active ? 'product.activated' : 'product.deactivated', $product, ['sku' => $product->sku]);

        return back()->with('status', $product->is_active ? 'Producto activado.' : 'Producto desactivado.');
    }

    private function audit(Request $request, string $action, Product $product, array $details): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => $product->getMorphClass(),
            'auditable_id' => $product->id,
            'details' => $details,
            'ip_address' => $request->ip(),
        ]);
    }
}
