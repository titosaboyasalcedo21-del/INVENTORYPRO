<x-guest-layout>
<h1 class="auth-title">Qué bueno verte de nuevo</h1>
<p class="auth-description">Inicia sesión para continuar a tu espacio de trabajo.</p>

<x-auth-session-status class="mb-3" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf
    <div class="mb-3">
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nombre@empresa.com" />
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <div class="mb-3">
        <x-input-label for="password" value="Contraseña" />
        <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña" />
        <x-input-error :messages="$errors->get('password')" />
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input id="remember" class="form-check-input" type="checkbox" name="remember">
            <label class="form-check-label" for="remember">Recordarme</label>
        </div>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        @endif
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Iniciar sesión</button>
</form>

@if (Route::has('register'))
    <p class="text-center text-secondary small mb-0 mt-4">¿Primera vez aquí? <a href="{{ route('register') }}" class="fw-semibold">Crea tu cuenta</a></p>
@endif
</x-guest-layout>
