<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Operaciones</li><li class="breadcrumb-item active" aria-current="page">Movimientos</li></ol></nav>
        <h1>Movimientos de inventario</h1><p>Cada cambio de existencias queda registrado con usuario, cantidad y saldo anterior.</p>
    </x-slot>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-5">
            <section class="form-card h-100">
                <div class="data-card-heading px-0"><div><h2>Ajustar existencias</h2><p>Para inventario inicial, mermas o correcciones.</p></div></div>
                @if($products->isEmpty())<div class="alert alert-warning small">Primero crea un producto activo.</div>@endif
                <form method="POST" action="{{ route('inventory.adjustments.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label" for="product_id">Producto <span class="text-danger">*</span></label><select class="form-select @error('product_id') is-invalid @enderror" name="product_id" id="product_id" required><option value="">Selecciona un producto</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} · {{ $product->sku }} ({{ $product->stock }} disp.)</option>@endforeach</select>@error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-3 mb-3"><div class="col-6"><label class="form-label" for="direction">Tipo <span class="text-danger">*</span></label><select class="form-select @error('direction') is-invalid @enderror" id="direction" name="direction" required><option value="in" @selected(old('direction') === 'in')>Entrada</option><option value="out" @selected(old('direction') === 'out')>Salida</option></select>@error('direction')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-6"><label class="form-label" for="quantity">Cantidad <span class="text-danger">*</span></label><input class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" type="number" min="1" max="1000000" value="{{ old('quantity', 1) }}" required>@error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                    <div class="mb-3"><label class="form-label" for="description">Motivo del ajuste <span class="text-danger">*</span></label><input class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}" maxlength="255" placeholder="Ej.: Conteo inicial, merma o corrección" required>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-primary w-100" type="submit" @disabled($products->isEmpty())><i class="bi bi-check2 me-1" aria-hidden="true"></i>Registrar ajuste</button>
                </form>
            </section>
        </div>
        <div class="col-12 col-lg-7">
            <section class="data-card h-100">
                <form class="filter-bar flex-wrap" method="GET" action="{{ route('inventory.movements.index') }}">
                    <div class="search-field"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" name="search" type="search" value="{{ request('search') }}" placeholder="Buscar producto o SKU" aria-label="Buscar movimientos"></div>
                    <select class="form-select filter-select" name="type" aria-label="Filtrar movimiento"><option value="">Todos los movimientos</option>@foreach(['adjustment'=>'Ajustes','sale'=>'Ventas','sale_return'=>'Devoluciones','purchase_received'=>'Recepciones','purchase_cancelled'=>'Cancelaciones'] as $value=>$label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Filtrar</button>
                </form>
                <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Producto</th><th>Fecha</th><th>Tipo</th><th>Cambio</th><th>Saldo</th><th>Usuario</th></tr></thead><tbody>
                @forelse($movements as $movement)<tr><td><a class="table-primary-link table-strong" href="{{ route('products.show', $movement->product) }}">{{ $movement->product->name }}</a><small class="table-subtext">{{ $movement->product->sku }}</small></td><td>{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($movement->type)) }}<small class="table-subtext">{{ $movement->description }}</small></td><td class="fw-semibold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td>{{ $movement->stock_before }} → <strong>{{ $movement->stock_after }}</strong></td><td>{{ $movement->user?->name ?? 'Sistema' }}</td></tr>
                @empty<tr><td colspan="6"><div class="empty-state compact"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><h2>Sin movimientos</h2><p>Los ajustes, ventas y recepciones quedarán registrados aquí.</p></div></td></tr>@endforelse
                </tbody></table></div>
                @if($movements->hasPages())<div class="pagination-wrap">{{ $movements->links() }}</div>@endif
            </section>
        </div>
    </div>
</x-app-layout>
