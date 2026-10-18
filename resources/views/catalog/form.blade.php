<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($section['key'].'.index') }}">{{ $section['plural'] }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ $record ? 'Editar' : 'Nuevo' }}</li></ol></nav>
        <h1>{{ $record ? 'Editar' : 'Nuevo' }} {{ $section['singular'] }}</h1>
        <p>Completa los datos del registro.</p>
    </x-slot>

    <section class="form-card">
        <form method="POST" action="{{ $record ? route($section['key'].'.update', $record) : route($section['key'].'.store') }}">
            @csrf
            @if($record) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="name">Nombre <span class="text-danger">*</span></label>
                    <input id="name" class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name', $record?->name) }}" maxlength="255" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if($section['key'] === 'categories')
                    <div class="col-12">
                        <label class="form-label" for="description">Descripción</label>
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3" maxlength="2000">{{ old('description', $record?->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    @if($section['key'] === 'suppliers')
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="company">Empresa</label>
                            <input id="company" class="form-control @error('company') is-invalid @enderror" type="text" name="company" value="{{ old('company', $record?->company) }}" maxlength="255">
                            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $record?->email) }}" maxlength="255" autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="phone">Teléfono</label>
                        <input id="phone" class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone', $record?->phone) }}" maxlength="40" autocomplete="tel">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="tax_id">Identificación fiscal</label>
                        <input id="tax_id" class="form-control @error('tax_id') is-invalid @enderror" type="text" name="tax_id" value="{{ old('tax_id', $record?->tax_id) }}" maxlength="80">
                        @error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Dirección</label>
                        <textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" rows="2" maxlength="2000">{{ old('address', $record?->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
            </div>
            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="{{ route($section['key'].'.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>{{ $record ? 'Guardar cambios' : 'Crear '.$section['singular'] }}</button>
            </div>
        </form>
    </section>
</x-app-layout>
