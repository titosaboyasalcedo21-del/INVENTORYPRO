<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">Catálogo</li><li class="breadcrumb-item active" aria-current="page">{{ $section['plural'] }}</li></ol></nav>
        <div class="page-heading-row">
            <div><h1>{{ $section['plural'] }}</h1><p>Administra los registros y conserva su historial.</p></div>
            <a class="btn btn-primary" href="{{ route($section['key'].'.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo {{ $section['singular'] }}</a>
        </div>
    </x-slot>

    <div class="data-card">
        <form class="filter-bar" method="GET" action="{{ route($section['key'].'.index') }}">
            <div class="search-field"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre{{ $section['key'] === 'categories' ? '' : ', correo o teléfono' }}" aria-label="Buscar {{ strtolower($section['plural']) }}"></div>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Buscar</button>
            @if ($search !== '')<a class="btn btn-light btn-sm" href="{{ route($section['key'].'.index') }}">Limpiar</a>@endif
            <span class="result-count">{{ $records->total() }} registros</span>
        </form>
        @if ($errors->has('record'))
            <div class="alert alert-warning mx-3 mb-0">{{ $errors->first('record') }}</div>
        @endif
        <div class="table-responsive">
            <table class="table data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        @if ($section['key'] === 'categories')
                            <th>Productos</th>
                        @else
                            @if ($section['key'] === 'suppliers')<th>Empresa</th>@endif
                            <th>Correo</th><th>Teléfono</th>
                        @endif
                        <th>Estado</th><th>Actualizado</th><th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td><strong class="table-strong">{{ $record->name }}</strong>@if($section['key'] === 'categories' && $record->description)<small class="table-subtext">{{ $record->description }}</small>@endif</td>
                        @if ($section['key'] === 'categories')
                            <td>{{ $record->products_count }}</td>
                        @else
                            @if ($section['key'] === 'suppliers')<td>{{ $record->company ?: '—' }}</td>@endif
                            <td>{{ $record->email ?: '—' }}</td><td>{{ $record->phone ?: '—' }}</td>
                        @endif
                        <td><span class="status-pill {{ $record->is_active ? 'status-complete' : 'status-cancelled' }}"><span></span>{{ $record->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>{{ $record->updated_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-light" href="{{ route($section['key'].'.edit', $record) }}" aria-label="Editar {{ $section['singular'] }} {{ $record->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route($section['key'].'.destroy', $record) }}" onsubmit="return confirm('¿{{ $record->is_active ? 'Desactivar' : 'Activar' }} este {{ $section['singular'] }}? El historial se conservará.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light {{ $record->is_active ? 'text-danger' : 'text-success' }}" type="submit" aria-label="{{ $record->is_active ? 'Desactivar' : 'Activar' }} {{ $record->name }}"><i class="bi {{ $record->is_active ? 'bi-archive' : 'bi-arrow-counterclockwise' }}" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $section['key'] === 'categories' ? 5 : ($section['key'] === 'suppliers' ? 7 : 6) }}">
                        <div class="empty-state"><i class="bi {{ $section['icon'] }}" aria-hidden="true"></i><h2>No hay {{ strtolower($section['plural']) }} todavía</h2><p>{{ $search ? 'Prueba otra búsqueda.' : 'Crea el primer registro para comenzar.' }}</p>@unless($search)<a class="btn btn-primary btn-sm" href="{{ route($section['key'].'.create') }}">Crear {{ $section['singular'] }}</a>@endunless</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())<div class="pagination-wrap">{{ $records->links() }}</div>@endif
    </div>
</x-app-layout>
