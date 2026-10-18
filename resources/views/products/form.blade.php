<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('products.index') }}">Productos</a></li><li class="breadcrumb-item active" aria-current="page">{{ $product ? 'Editar' : 'Nuevo' }}</li></ol></nav>
        <h1>{{ $product ? 'Editar producto' : 'Nuevo producto' }}</h1>
        <p>Configura el catálogo. Las existencias se cambian desde movimientos de inventario.</p>
    </x-slot>

    <section class="form-card">
        <form method="POST" action="{{ $product ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data">
            @csrf @if($product) @method('PUT') @endif
            <div class="form-section-title">Información del producto</div>
            <div class="row g-3">
                <div class="col-12 col-md-6"><label class="form-label" for="name">Nombre <span class="text-danger">*</span></label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product?->name) }}" maxlength="255" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6"><label class="form-label" for="sku">SKU <span class="text-danger">*</span></label><input class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" maxlength="80" required>@error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6"><label class="form-label" for="category_id">Categoría</label><select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="description">Descripción</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="4000">{{ old('description', $product?->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="form-section-title mt-4">Precios y existencias</div>
            <div class="row g-3">
                <div class="col-12 col-md-4"><label class="form-label" for="purchase_price">Último costo de compra ({{ config('inventory.currency_symbol') }})</label><input class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" value="{{ old('purchase_price', $product?->purchase_price ?? '0.00') }}" required>@error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-4"><label class="form-label" for="sale_price">Precio de venta ({{ config('inventory.currency_symbol') }})</label><input class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" type="number" step="0.01" min="0.01" value="{{ old('sale_price', $product?->sale_price) }}" required>@error('sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-4"><label class="form-label" for="minimum_stock">Stock mínimo</label><input class="form-control @error('minimum_stock') is-invalid @enderror" id="minimum_stock" name="minimum_stock" type="number" step="1" min="0" value="{{ old('minimum_stock', $product?->minimum_stock ?? 0) }}" required>@error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="image">Imagen del producto</label><input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror<p class="form-hint">JPG, PNG o WebP; máximo 2 MB. No uses imágenes con información sensible.</p>
                    @if($product?->image_path)<img class="product-preview mt-2" src="{{ asset('storage/'.$product->image_path) }}" alt="Imagen actual de {{ $product->name }}">@endif
                </div>
            </div>
            @if($product)<div class="form-note mt-4"><i class="bi bi-info-circle" aria-hidden="true"></i>Stock actual: <strong>{{ number_format($product->stock) }}</strong>. Registra cualquier ajuste en Movimientos para mantener el historial.</div>@endif
            <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>{{ $product ? 'Guardar cambios' : 'Crear producto' }}</button></div>
        </form>
    </section>
</x-app-layout>
