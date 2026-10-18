<x-guest-layout>
<h1 class="auth-title">Recuperar contraseña</h1>
<p class="auth-description">Indica el correo de tu cuenta y te enviaremos un enlace para crear una contraseña nueva.</p>

<x-auth-session-status class="mb-3" :status="session('status')" />

<form method="POST" action="{{ route('password.email') }}" class="auth-form">
    @csrf
    <div class="mb-4">
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nombre@empresa.com" />
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-envelope me-2" aria-hidden="true"></i>Enviar enlace de recuperación</button>
</form>

<p class="text-center text-secondary small mb-0 mt-4"><a href="{{ route('login') }}">Volver al inicio de sesión</a></p>
</x-guest-layout>
