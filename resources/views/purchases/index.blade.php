<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Operaciones</li><li class="breadcrumb-item active" aria-current="page">Compras</li></ol></nav>
        <div class="page-heading-row"><div><h1>Compras</h1><p>Registra pedidos y recibe mercancía para actualizar el inventario.</p></div><a class="btn btn-primary" href="{{ route('purchases.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva compra</a></div>
    </x-slot>

    <section class="data-card">
        <form class="filter-bar flex-wrap" method="GET" action="{{ route('purchases.index') }}">
            <div class="search-field"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar comprobante o proveedor" aria-label="Buscar compras"></div>
            <select class="form-select filter-select" name="status" aria-label="Filtrar por estado"><option value="">Todos los estados</option>@foreach(['pending'=>'Pendiente','received'=>'Recibida','cancelled'=>'Cancelada'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Filtrar</button><span class="result-count">{{ $purchases->total() }} compras</span>
        </form>
        <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Comprobante</th><th>Proveedor</th><th>Fecha</th><th>Estado</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
        @forelse($purchases as $purchase)<tr><td><a class="table-primary-link table-strong" href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference }}</a><small class="table-subtext">{{ $purchase->user?->name ?? 'Usuario eliminado' }}</small></td><td>{{ $purchase->supplier->name }}</td><td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td><td><span class="status-pill {{ $purchase->status === 'received' ? 'status-complete' : ($purchase->status === 'pending' ? 'status-pending' : 'status-cancelled') }}"><span></span>{{ ['pending'=>'Pendiente','received'=>'Recibida','cancelled'=>'Cancelada'][$purchase->status] }}</span></td><td class="text-end fw-semibold">{{ config('inventory.currency_symbol') }} {{ number_format($purchase->total, 2) }}</td><td class="text-end"><a class="btn btn-sm btn-light" href="{{ route('purchases.show', $purchase) }}" aria-label="Ver compra {{ $purchase->reference }}"><i class="bi bi-arrow-right" aria-hidden="true"></i></a></td></tr>
        @empty<tr><td colspan="6"><div class="empty-state"><i class="bi bi-bag" aria-hidden="true"></i><h2>No hay compras todavía</h2><p>Registra una compra y recíbela cuando llegue la mercancía.</p><a class="btn btn-primary btn-sm" href="{{ route('purchases.create') }}">Crear compra</a></div></td></tr>@endforelse
        </tbody></table></div>
        @if($purchases->hasPages())<div class="pagination-wrap">{{ $purchases->links() }}</div>@endif
    </section>
</x-app-layout>
