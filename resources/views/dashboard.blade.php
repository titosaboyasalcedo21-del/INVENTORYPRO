<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active" aria-current="page">General</li></ol></nav>
        <h1>Panel principal</h1>
        <p>Indicadores reales de inventario y actividad del mes.</p>
    </x-slot>

    <section class="dashboard-welcome" aria-labelledby="welcome-heading">
        <span class="welcome-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> Resumen de operaciones</span>
        <h2 id="welcome-heading">Hola, {{ auth()->user()->name }}</h2>
        <p>Consulta tus existencias, ventas y compras recientes en un solo lugar.</p>
        <a class="btn" href="{{ route('sales.create') }}"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Registrar venta</a>
    </section>

    <section class="row g-3 mt-1" aria-label="Indicadores del mes">
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-top"><span>Productos activos</span><span class="metric-icon metric-blue"><i class="bi bi-box-seam" aria-hidden="true"></i></span></div>
                <strong>{{ number_format($activeProducts) }}</strong>
                <small>Productos en catálogo</small>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-top"><span>Ventas del mes</span><span class="metric-icon metric-green"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span></div>
                <strong>{{ config('inventory.currency_symbol') }} {{ number_format($monthlySales, 2) }}</strong>
                <small>Ventas confirmadas</small>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-top"><span>Compras del mes</span><span class="metric-icon metric-purple"><i class="bi bi-bag-check" aria-hidden="true"></i></span></div>
                <strong>{{ config('inventory.currency_symbol') }} {{ number_format($monthlyPurchases, 2) }}</strong>
                <small>Mercancía recibida</small>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card {{ $lowStockCount ? 'metric-warning' : '' }}">
                <div class="metric-top"><span>Stock bajo</span><span class="metric-icon metric-amber"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span></div>
                <strong>{{ number_format($lowStockCount) }}</strong>
                <small>Productos en mínimo o agotados</small>
            </article>
        </div>
    </section>

    <section class="row g-3 mt-1">
        <div class="col-12 col-xl-8">
            <article class="data-card h-100">
                <div class="data-card-heading"><div><h2>Ventas recientes</h2><p>Últimos 14 días</p></div><a class="text-link" href="{{ route('reports.index') }}">Ver reportes <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                <div class="chart-container"><canvas id="sales-chart" aria-label="Gráfico de ventas de los últimos 14 días" role="img"></canvas></div>
            </article>
        </div>
        <div class="col-12 col-xl-4">
            <article class="data-card h-100">
                <div class="data-card-heading"><div><h2>Más vendidos</h2><p>Este mes</p></div></div>
                @forelse ($topProducts as $product)
                    <div class="rank-row">
                        <span class="rank-icon"><i class="bi bi-box" aria-hidden="true"></i></span>
                        <span class="rank-name"><strong>{{ $product->product_name }}</strong><small>{{ $product->sku }}</small></span>
                        <span class="rank-value">{{ number_format($product->units) }} uds.</span>
                    </div>
                @empty
                    <div class="empty-state compact"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>Aún no hay ventas confirmadas en este mes.</p></div>
                @endforelse
            </article>
        </div>
    </section>

    <section class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <article class="data-card h-100">
                <div class="data-card-heading"><div><h2>Últimas ventas</h2><p>Operaciones completadas</p></div><a class="text-link" href="{{ route('sales.index') }}">Ver todas <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr><th>Comprobante</th><th>Cliente</th><th>Fecha</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td><a class="table-primary-link" href="{{ route('sales.show', $sale) }}">{{ $sale->reference }}</a></td>
                                <td>{{ $sale->customer?->name ?? 'Venta general' }}</td>
                                <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                <td class="text-end fw-semibold">{{ config('inventory.currency_symbol') }} {{ number_format($sale->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state compact"><i class="bi bi-receipt" aria-hidden="true"></i><p>Las ventas confirmadas aparecerán aquí.</p><a href="{{ route('sales.create') }}">Registrar la primera venta</a></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
        <div class="col-12 col-xl-5">
            <article class="data-card h-100">
                <div class="data-card-heading"><div><h2>Movimientos de inventario</h2><p>Actividad reciente</p></div><a class="text-link" href="{{ route('inventory.movements.index') }}">Ver todos <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                @forelse ($recentMovements as $movement)
                    <div class="activity-row">
                        <span class="activity-icon {{ $movement->quantity > 0 ? 'activity-in' : 'activity-out' }}"><i class="bi {{ $movement->quantity > 0 ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}" aria-hidden="true"></i></span>
                        <span class="activity-copy"><strong>{{ $movement->product?->name ?? 'Producto eliminado' }}</strong><small>{{ $movement->description ?: str_replace('_', ' ', ucfirst($movement->type)) }} · {{ $movement->created_at->format('d/m H:i') }}</small></span>
                        <span class="activity-quantity {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                    </div>
                @empty
                    <div class="empty-state compact"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><p>Los cambios de stock se registrarán aquí.</p></div>
                @endforelse
            </article>
        </div>
    </section>

    <script id="sales-chart-data" type="application/json">@json(['labels' => $salesLabels, 'amounts' => $salesAmounts])</script>
</x-app-layout>
