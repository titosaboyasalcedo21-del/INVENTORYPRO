<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'InventoryPro') }} | Gestión de inventario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card text-center">
            <a class="brand-lockup justify-content-center mb-4" href="{{ route('login') }}">
                <span class="brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
                <span class="brand-name">Inventory<span>Pro</span></span>
            </a>
            <h1 class="auth-title">Tu espacio de trabajo, en orden.</h1>
            <p class="auth-description mb-4">Accede a InventoryPro para administrar tu cuenta y continuar con la configuración del sistema.</p>
            <div class="d-grid gap-2">
                @auth
                    <a class="btn btn-primary" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2 me-2" aria-hidden="true"></i>Ir al panel</a>
                @else
                    <a class="btn btn-primary" href="{{ route('login') }}">Iniciar sesión</a>
                    @if (Route::has('register'))
                        <a class="btn btn-outline-secondary" href="{{ route('register') }}">Crear cuenta</a>
                    @endif
                @endauth
            </div>
        </section>
        <p class="auth-footnote"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Acceso seguro a tu espacio de trabajo</p>
    </main>
</body>
</html>
