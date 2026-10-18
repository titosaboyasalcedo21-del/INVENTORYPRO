<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-currency="{{ config('inventory.currency') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'InventoryPro') }}{{ isset($header) ? ' | Panel' : '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="offcanvas-lg offcanvas-start inventory-sidebar" tabindex="-1" id="inventorySidebar" aria-labelledby="sidebarTitle">
            <div class="offcanvas-header sidebar-brand">
                <a class="brand-lockup" href="{{ route('dashboard') }}">
                    <span class="brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
                    <span id="sidebarTitle" class="brand-name">Inventory<span>Pro</span></span>
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
            </div>

            <div class="offcanvas-body sidebar-body">
                <nav class="sidebar-nav" aria-label="Navegación principal">
                    <span class="nav-section-label">GENERAL</span>
                    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Panel principal</span>
                    </a>

                    <span class="nav-section-label mt-4">OPERACIONES</span>
                    @foreach ([
                        ['bi-box-seam', 'Productos', 'products.index'],
                        ['bi-tags', 'Categorías', 'categories.index'],
                        ['bi-cart-check', 'Ventas', 'sales.index'],
                        ['bi-bag', 'Compras', 'purchases.index'],
                        ['bi-arrow-left-right', 'Movimientos', 'inventory.movements.index'],
                    ] as [$icon, $label, $route])
                        <a class="sidebar-link {{ request()->routeIs(str_replace('.index', '.*', $route)) ? 'active' : '' }}" href="{{ route($route) }}" @if(request()->routeIs(str_replace('.index', '.*', $route))) aria-current="page" @endif>
                            <i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                        </a>
                    @endforeach

                    <span class="nav-section-label mt-4">CONTACTOS</span>
                    @foreach ([
                        ['bi-truck', 'Proveedores', 'suppliers.index'],
                        ['bi-people', 'Clientes', 'customers.index'],
                    ] as [$icon, $label, $route])
                        <a class="sidebar-link {{ request()->routeIs(str_replace('.index', '.*', $route)) ? 'active' : '' }}" href="{{ route($route) }}" @if(request()->routeIs(str_replace('.index', '.*', $route))) aria-current="page" @endif>
                            <i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                        </a>
                    @endforeach

                    <span class="nav-section-label mt-4">ANÁLISIS</span>
                    <a class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                        <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Reportes</span>
                    </a>
                </nav>

                <div class="sidebar-bottom">
                    <div class="sidebar-help">
                        <span class="help-icon"><i class="bi bi-info-lg" aria-hidden="true"></i></span>
                        <div><strong>Centro de ayuda</strong><small>Inventario y ventas</small></div>
                    </div>
                    <div class="sidebar-version">© {{ now()->year }} InventoryPro</div>
                </div>
            </div>
        </aside>

        <div class="main-column">
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#inventorySidebar" aria-controls="inventorySidebar" aria-label="Abrir menú">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <div class="topbar-context"><span>InventoryPro</span><i class="bi bi-chevron-right" aria-hidden="true"></i><strong>{{ request()->routeIs('profile.*') ? 'Mi perfil' : (request()->routeIs('dashboard') ? 'Panel principal' : ucfirst(explode('.', (string) request()->route()->getName())[0])) }}</strong></div>
                </div>
                <div class="dropdown">
                    <button class="account-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="account-copy"><strong>{{ auth()->user()->name }}</strong><small>Mi cuenta</small></span>
                        <i class="bi bi-chevron-down account-chevron" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-menu">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Mi perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="main-content">
                @if (isset($header))
                    <div class="page-heading">{{ $header }}</div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="status">
                        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="app-footer"><span>InventoryPro</span><span>Gestión clara, inventario bajo control.</span></footer>
        </div>
    </div>
</body>
</html>
