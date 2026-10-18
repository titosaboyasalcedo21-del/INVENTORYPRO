<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Operaciones</li><li class="breadcrumb-item active" aria-current="page">Ventas</li></ol></nav>
        <div class="page-heading-row"><div><h1>Ventas</h1><p>Consulta comprobantes y registra nuevas operaciones.</p></div><a class="btn btn-primary" href="{{ route('sales.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva venta</a></div>
    </x-slot>

    <section class="data-card">
        <form class="filter-bar flex-wrap" method="GET" action="{{ route('sales.index') }}">
            <div class="search-field"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar comprobante o cliente" aria-label="Buscar ventas"></div>
            <select class="form-select filter-select" name="status" aria-label="Filtrar por estado"><option value="">Todos los estados</option><option value="completed" @selected(request('status') === 'completed')>Completadas</option><option value="cancelled" @selected(request('status') === 'cancelled')>Canceladas</option></select>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Filtrar</button><span class="result-count">{{ $sales->total() }} ventas</span>
        </form>
        <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Comprobante</th><th>Cliente</th><th>Vendedor</th><th>Fecha</th><th>Estado</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
        @forelse($sales as $sale)<tr><td><a class="table-primary-link table-strong" href="{{ route('sales.show', $sale) }}">{{ $sale->reference }}</a></td><td>{{ $sale->customer?->name ?? 'Venta general' }}</td><td>{{ $sale->user?->name ?? 'Usuario eliminado' }}</td><td>{{ $sale->created_at->format('d/m/Y H:i') }}</td><td><span class="status-pill {{ $sale->status === 'completed' ? 'status-complete' : 'status-cancelled' }}"><span></span>{{ $sale->status === 'completed' ? 'Completada' : 'Cancelada' }}</span></td><td class="text-end fw-semibold">{{ config('inventory.currency_symbol') }} {{ number_format($sale->total, 2) }}</td><td class="text-end"><a class="btn btn-sm btn-light" href="{{ route('sales.show', $sale) }}" aria-label="Ver venta {{ $sale->reference }}"><i class="bi bi-arrow-right" aria-hidden="true"></i></a></td></tr>
        @empty<tr><td colspan="7"><div class="empty-state"><i class="bi bi-receipt" aria-hidden="true"></i><h2>No hay ventas todavía</h2><p>Registra una venta para consultar aquí sus comprobantes.</p><a class="btn btn-primary btn-sm" href="{{ route('sales.create') }}">Registrar venta</a></div></td></tr>@endforelse
        </tbody></table></div>
        @if($sales->hasPages())<div class="pagination-wrap">{{ $sales->links() }}</div>@endif
    </section>
</x-app-layout>
