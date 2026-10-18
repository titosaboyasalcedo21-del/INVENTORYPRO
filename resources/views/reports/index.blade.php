<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Análisis</li><li class="breadcrumb-item active" aria-current="page">Reportes</li></ol></nav>
        <h1>Reportes</h1><p>Consulta las ventas y compras recibidas por período. Cifras calculadas desde los comprobantes registrados.</p>
    </x-slot>

    <section class="data-card mb-3">
        <form class="filter-bar flex-wrap" method="GET" action="{{ route('reports.index') }}">
            <div><label class="form-label mb-1 small fw-semibold" for="from">Desde</label><input class="form-control" id="from" name="from" type="date" value="{{ $from }}" required></div>
            <div><label class="form-label mb-1 small fw-semibold" for="to">Hasta</label><input class="form-control" id="to" name="to" type="date" value="{{ $to }}" required></div>
            <button class="btn btn-primary btn-sm align-self-end" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Aplicar período</button>
            <div class="report-exports ms-lg-auto"><a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.csv', ['from' => $from, 'to' => $to]) }}"><i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>Exportar CSV</a><a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.pdf', ['from' => $from, 'to' => $to]) }}"><i class="bi bi-filetype-pdf me-1" aria-hidden="true"></i>Descargar PDF</a></div>
            @error('from')<div class="w-100 text-danger small">{{ $message }}</div>@enderror @error('to')<div class="w-100 text-danger small">{{ $message }}</div>@enderror
        </form>
    </section>

    <section class="row g-3 mb-3" aria-label="Totales del período">
        <div class="col-12 col-sm-6 col-xl-4"><article class="metric-card"><div class="metric-top"><span>Ventas confirmadas</span><span class="metric-icon metric-green"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span></div><strong>{{ config('inventory.currency_symbol') }} {{ number_format($sales['total'], 2) }}</strong><small>{{ number_format($sales['count']) }} comprobantes completados</small></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="metric-card"><div class="metric-top"><span>Compras recibidas</span><span class="metric-icon metric-purple"><i class="bi bi-bag-check" aria-hidden="true"></i></span></div><strong>{{ config('inventory.currency_symbol') }} {{ number_format($purchases['total'], 2) }}</strong><small>{{ number_format($purchases['count']) }} recepciones completadas</small></article></div>
        <div class="col-12 col-xl-4"><article class="metric-card"><div class="metric-top"><span>Productos más vendidos</span><span class="metric-icon metric-blue"><i class="bi bi-box-seam" aria-hidden="true"></i></span></div><strong>{{ number_format($topProducts->sum('units')) }}</strong><small>Unidades en el período seleccionado</small></article></div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8"><article class="data-card h-100"><div class="data-card-heading"><div><h2>Ventas por día</h2><p>Período {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</p></div></div><div class="chart-container report-chart"><canvas id="report-chart" aria-label="Ventas diarias del período" role="img"></canvas></div><script id="report-chart-data" type="application/json">@json(['labels' => $labels, 'amounts' => $amounts, 'currency' => config('inventory.currency')])</script></article></div>
        <div class="col-12 col-xl-4"><article class="data-card h-100"><div class="data-card-heading"><div><h2>Productos más vendidos</h2><p>Por unidades vendidas</p></div></div>
            @forelse($topProducts as $product)<div class="rank-row"><span class="rank-icon"><i class="bi bi-box" aria-hidden="true"></i></span><span class="rank-name"><strong>{{ $product->product_name }}</strong><small>{{ $product->sku }}</small></span><span class="rank-value">{{ number_format($product->units) }} uds.</span></div>
            @empty<div class="empty-state compact"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>Sin ventas completadas durante este período.</p></div>@endforelse
        </article></div>
    </section>
</x-app-layout>
