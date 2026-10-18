<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('products.index') }}">Productos</a></li><li class="breadcrumb-item active" aria-current="page">{{ $product->sku }}</li></ol></nav>
        <div class="page-heading-row"><div><h1>{{ $product->name }}</h1><p>Detalle de producto e historial de inventario.</p></div><a class="btn btn-outline-secondary" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a></div>
    </x-slot>

    <section class="data-card product-detail-card">
        @if($product->image_path)<img class="product-detail-image" src="{{ asset('storage/'.$product->image_path) }}" alt="Imagen de {{ $product->name }}">@else<span class="product-detail-image product-thumb-empty"><i class="bi bi-box-seam" aria-hidden="true"></i></span>@endif
        <div class="product-detail-main"><span class="sku-code">{{ $product->sku }}</span><h2>{{ $product->name }}</h2><p>{{ $product->description ?: 'Sin descripción.' }}</p><span class="status-pill {{ $product->is_active ? 'status-complete' : 'status-cancelled' }}"><span></span>{{ $product->is_active ? 'Activo' : 'Inactivo' }}</span></div>
        <div class="product-detail-stats"><div><small>Categoría</small><strong>{{ $product->category?->name ?? 'Sin categoría' }}</strong></div><div><small>Precio de venta</small><strong>{{ config('inventory.currency_symbol') }} {{ number_format($product->sale_price, 2) }}</strong></div><div><small>Stock actual</small><strong>{{ number_format($product->stock) }} unidades</strong></div><div><small>Stock mínimo</small><strong>{{ number_format($product->minimum_stock) }} unidades</strong></div></div>
    </section>

    <section class="data-card mt-3">
        <div class="data-card-heading"><div><h2>Historial de movimientos</h2><p>Entradas, salidas y ajustes registrados</p></div><a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.movements.index') }}">Nuevo ajuste</a></div>
        <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Fecha</th><th>Movimiento</th><th>Descripción</th><th>Usuario</th><th>Antes</th><th>Cambio</th><th>Después</th></tr></thead><tbody>
        @forelse($movements as $movement)<tr><td>{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($movement->type)) }}</td><td>{{ $movement->description ?: '—' }}</td><td>{{ $movement->user?->name ?? 'Sistema' }}</td><td>{{ $movement->stock_before }}</td><td class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td class="fw-semibold">{{ $movement->stock_after }}</td></tr>
        @empty<tr><td colspan="7"><div class="empty-state compact"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><p>Este producto todavía no tiene movimientos.</p></div></td></tr>@endforelse
        </tbody></table></div>
        @if($movements->hasPages())<div class="pagination-wrap">{{ $movements->links() }}</div>@endif
    </section>
</x-app-layout>
