<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Compras</a></li><li class="breadcrumb-item active" aria-current="page">{{ $purchase->reference }}</li></ol></nav>
        <div class="page-heading-row"><div><h1>Compra {{ $purchase->reference }}</h1><p>Registrada el {{ $purchase->created_at->format('d/m/Y H:i') }} por {{ $purchase->user?->name ?? 'Usuario eliminado' }}.</p></div><span class="status-pill {{ $purchase->status === 'received' ? 'status-complete' : ($purchase->status === 'pending' ? 'status-pending' : 'status-cancelled') }}"><span></span>{{ ['pending'=>'Pendiente','received'=>'Recibida','cancelled'=>'Cancelada'][$purchase->status] }}</span></div>
    </x-slot>

    @if($errors->has('purchase'))<div class="alert alert-danger">{{ $errors->first('purchase') }}</div>@endif
    <section class="data-card">
        <div class="document-summary"><div><small>Proveedor</small><strong>{{ $purchase->supplier->name }}</strong>@if($purchase->supplier->company)<span>{{ $purchase->supplier->company }}</span>@endif</div><div><small>Fecha de recepción</small><strong>{{ $purchase->received_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</strong></div><div><small>Notas</small><strong>{{ $purchase->notes ?: '—' }}</strong></div></div>
        <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Producto</th><th>SKU</th><th class="text-end">Cantidad</th><th class="text-end">Costo unitario</th><th class="text-end">Importe</th></tr></thead><tbody>
        @foreach($purchase->items as $item)<tr><td>{{ $item->product_name }}</td><td><code class="sku-code">{{ $item->sku }}</code></td><td class="text-end">{{ number_format($item->quantity) }}</td><td class="text-end">{{ config('inventory.currency_symbol') }} {{ number_format($item->unit_cost, 2) }}</td><td class="text-end fw-semibold">{{ config('inventory.currency_symbol') }} {{ number_format($item->line_total, 2) }}</td></tr>@endforeach
        </tbody></table></div>
        <div class="document-totals"><div><span>Subtotal</span><strong>{{ config('inventory.currency_symbol') }} {{ number_format($purchase->subtotal, 2) }}</strong></div><div><span>Impuesto ({{ number_format(config('inventory.tax_rate'), 2) }}%)</span><strong>{{ config('inventory.currency_symbol') }} {{ number_format($purchase->tax_amount, 2) }}</strong></div><div class="total"><span>Total</span><strong>{{ config('inventory.currency_symbol') }} {{ number_format($purchase->total, 2) }}</strong></div></div>
        @if($purchase->status !== 'cancelled')
            <div class="form-actions">
                @if($purchase->status === 'pending')<form method="POST" action="{{ route('purchases.receive', $purchase) }}" onsubmit="return confirm('¿Confirmas la recepción? Esta acción actualizará el stock y no se puede repetir.');">@csrf<button class="btn btn-primary" type="submit"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Recibir mercancía</button></form>@endif
                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" onsubmit="return confirm('¿Cancelar esta compra? Si ya se recibió, las existencias se reducirán mediante movimientos compensatorios.');">@csrf<button class="btn btn-outline-danger" type="submit">Cancelar compra</button></form>
            </div>
        @endif
    </section>
</x-app-layout>
