<x-guest-layout>
<h1 class="auth-title">Verifica tu correo</h1>
<p class="auth-description">Antes de continuar, abre el enlace de verificación que enviamos a tu correo electrónico. Si no lo encuentras, puedes solicitar otro.</p>

@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success small" role="status"><i class="bi bi-check-circle me-2" aria-hidden="true"></i>Enviamos un nuevo enlace de verificación a tu correo.</div>
@endif

<div class="auth-form d-grid gap-2">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-envelope-arrow-up me-2" aria-hidden="true"></i>Reenviar enlace</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-secondary w-100" type="submit">Cerrar sesión</button>
    </form>
</div>
</x-guest-layout>
