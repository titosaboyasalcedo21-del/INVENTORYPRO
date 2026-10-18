<x-guest-layout>
<h1 class="auth-title">Elige una contraseña nueva</h1>
<p class="auth-description">Usa una contraseña segura que no hayas utilizado antes.</p>

<form method="POST" action="{{ route('password.store') }}" class="auth-form">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="mb-3">
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <div class="mb-3">
        <x-input-label for="password" value="Contraseña nueva" />
        <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" />
    </div>
    <div class="mb-4">
        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
        <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password_confirmation')" />
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Actualizar contraseña</button>
</form>
</x-guest-layout>
