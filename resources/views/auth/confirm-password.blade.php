<x-guest-layout>
<h1 class="auth-title">Confirma tu identidad</h1>
<p class="auth-description">Para continuar, confirma la contraseña de tu cuenta.</p>

<form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
    @csrf
    <div class="mb-4">
        <x-input-label for="password" value="Contraseña" />
        <x-text-input id="password" type="password" name="password" required autocomplete="current-password" autofocus />
        <x-input-error :messages="$errors->get('password')" />
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Confirmar</button>
</form>
</x-guest-layout>
