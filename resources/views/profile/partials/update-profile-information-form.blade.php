<section class="profile-card" aria-labelledby="profile-info-heading">
    <h2 id="profile-info-heading">Información personal</h2>
    <p>Actualiza el nombre y el correo asociados a tu cuenta.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <x-input-label for="name" value="Nombre completo" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div class="col-12 col-md-6">
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert alert-warning small mt-3 mb-0">
                Tu correo todavía no está verificado.
                <button form="send-verification" class="btn btn-link btn-sm p-0 ms-1 align-baseline">Reenviar enlace</button>
                @if (session('status') === 'verification-link-sent')
                    <span class="d-block mt-1" role="status">Enviamos un nuevo enlace de verificación.</span>
                @endif
            </div>
        @endif

        <div class="d-flex align-items-center gap-3 mt-4">
            <button class="btn btn-primary btn-sm px-3" type="submit">Guardar cambios</button>
            @if (session('status') === 'profile-updated')
                <span class="text-success small" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Los cambios se guardaron.</span>
            @endif
        </div>
    </form>
</section>
