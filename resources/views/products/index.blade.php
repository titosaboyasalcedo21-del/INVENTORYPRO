<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Operaciones</li><li class="breadcrumb-item active" aria-current="page">Productos</li></ol></nav>
        <div class="page-heading-row"><div><h1>Productos</h1><p>Administra artículos, precios y niveles de existencias.</p></div><a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo producto</a></div>
    </x-slot>

    <section class="data-card">
        <form class="filter-bar flex-wrap" method="GET" action="{{ route('products.index') }}">
            <div class="search-field"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre o SKU" aria-label="Buscar productos"></div>
            <select class="form-select filter-select" name="stock" aria-label="Filtrar por existencias">
                <option value="">Todos los niveles</option><option value="low" @selected($stockFilter === 'low')>Stock bajo</option><option value="available" @selected($stockFilter === 'available')>Disponibles</option>
            </select>
            <select class="form-select filter-select" name="status" aria-label="Filtrar por estado">
                <option value="active" @selected($statusFilter === 'active')>Activos</option><option value="inactive" @selected($statusFilter === 'inactive')>Inactivos</option><option value="all" @selected($statusFilter === 'all')>Todos</option>
            </select>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Filtrar</button>
            <span class="result-count">{{ $products->total() }} productos</span>
        </form>

        <div class="table-responsive">
            <table class="table data-table product-table align-middle mb-0">
                <thead><tr><th>Producto</th><th>SKU</th><th>Categoría</th><th>Precio de venta</th><th>Existencias</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td><div class="product-cell">
                            @if($product->image_path)<img class="product-thumb" src="{{ asset('storage/'.$product->image_path) }}" alt="" loading="lazy">@else<span class="product-thumb product-thumb-empty"><i class="bi bi-box" aria-hidden="true"></i></span>@endif
                            <span><a class="table-primary-link table-strong" href="{{ route('products.show', $product) }}">{{ $product->name }}</a><small class="table-subtext">{{ $product->is_active ? 'Producto activo' : 'Archivado' }}</small></span>
                        </div></td>
                        <td><code class="sku-code">{{ $product->sku }}</code></td>
                        <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                        <td>{{ config('inventory.currency_symbol') }} {{ number_format($product->sale_price, 2) }}</td>
                        <td><strong>{{ number_format($product->stock) }}</strong><small class="table-subtext">Mínimo {{ number_format($product->minimum_stock) }}</small></td>
                        <td><span class="status-pill {{ $product->stock_status === 'available' ? 'status-complete' : ($product->stock_status === 'low' ? 'status-pending' : 'status-cancelled') }}"><span></span>{{ $product->stock_status === 'available' ? 'Disponible' : ($product->stock_status === 'low' ? 'Stock bajo' : 'Agotado') }}</span></td>
                        <td><div class="table-actions">
                            <a class="btn btn-sm btn-light" href="{{ route('products.show', $product) }}" aria-label="Ver {{ $product->name }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <a class="btn btn-sm btn-light" href="{{ route('products.edit', $product) }}" aria-label="Editar {{ $product->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('¿{{ $product->is_active ? 'Desactivar' : 'Activar' }} este producto? Su historial se conservará.');">@csrf @method('DELETE')<button class="btn btn-sm btn-light {{ $product->is_active ? 'text-danger' : 'text-success' }}" type="submit" aria-label="{{ $product->is_active ? 'Desactivar' : 'Activar' }} {{ $product->name }}"><i class="bi {{ $product->is_active ? 'bi-archive' : 'bi-arrow-counterclockwise' }}" aria-hidden="true"></i></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-box-seam" aria-hidden="true"></i><h2>No encontramos productos</h2><p>{{ $search || $stockFilter ? 'Prueba ajustar los filtros.' : 'Crea el primer producto de tu catálogo.' }}</p>@unless($search || $stockFilter)<a class="btn btn-primary btn-sm" href="{{ route('products.create') }}">Crear producto</a>@endunless</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())<div class="pagination-wrap">{{ $products->links() }}</div>@endif
    </section>
</x-app-layout>
