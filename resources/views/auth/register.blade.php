<x-guest-layout>
<h1 class="auth-title">Crea tu cuenta</h1>
<p class="auth-description">Configura tu acceso personal a InventoryPro.</p>

<form method="POST" action="{{ route('register') }}" class="auth-form">
    @csrf
    <div class="mb-3">
        <x-input-label for="name" value="Nombre completo" />
        <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Tu nombre" />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div class="mb-3">
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nombre@empresa.com" />
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <div class="mb-3">
        <x-input-label for="password" value="Contraseña" />
        <x-text-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Crea una contraseña segura" />
        <x-input-error :messages="$errors->get('password')" />
    </div>
    <div class="mb-4">
        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
        <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Escribe la contraseña de nuevo" />
        <x-input-error :messages="$errors->get('password_confirmation')" />
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-person-plus me-2" aria-hidden="true"></i>Crear cuenta</button>
</form>

<p class="text-center text-secondary small mb-0 mt-4">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="fw-semibold">Inicia sesión</a></p>
</x-guest-layout>
