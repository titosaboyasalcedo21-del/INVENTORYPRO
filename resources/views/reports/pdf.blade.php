<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #203450; font-size: 10px; }
        h1 { color: #15243a; font-size: 20px; margin: 0 0 5px; }
        p { color: #748399; margin: 0 0 18px; }
        .summary { width: 100%; margin-bottom: 18px; }
        .summary td { width: 33%; padding: 10px; background: #f2f5fa; border: 1px solid #e3e9f1; }
        .summary strong { display: block; margin-top: 5px; color: #15243a; font-size: 13px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { color: #52637b; background: #f2f5fa; text-align: left; }
        .data th, .data td { padding: 7px 8px; border: 1px solid #e3e9f1; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>InventoryPro · Reporte de ventas</h1>
    <p>Período: {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }} · Generado {{ now()->format('d/m/Y H:i') }}</p>
    <table class="summary"><tr><td>Ventas completadas<strong>{{ $salesCount }}</strong></td><td>Total de ventas<strong>{{ $currency }} {{ number_format($salesTotal, 2) }}</strong></td><td>Compras recibidas<strong>{{ $currency }} {{ number_format($purchasesTotal, 2) }}</strong></td></tr></table>
    <table class="data">
        <thead><tr><th>Fecha</th><th>Comprobante</th><th>Cliente</th><th>Vendedor</th><th>Estado</th><th class="right">Total ({{ $currency }})</th></tr></thead>
        <tbody>@forelse($records as $sale)<tr><td>{{ $sale->created_at->format('d/m/Y H:i') }}</td><td>{{ $sale->reference }}</td><td>{{ $sale->customer?->name ?? 'Venta general' }}</td><td>{{ $sale->user?->name ?? 'Usuario eliminado' }}</td><td>{{ $sale->status === 'completed' ? 'Completada' : 'Cancelada' }}</td><td class="right">{{ number_format($sale->total, 2) }}</td></tr>@empty<tr><td colspan="6">No hay ventas en el período consultado.</td></tr>@endforelse</tbody>
    </table>
</body>
</html>
