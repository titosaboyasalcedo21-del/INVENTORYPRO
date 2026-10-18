<section class="profile-card" aria-labelledby="update-password-heading">
    <h2 id="update-password-heading">Cambiar contraseña</h2>
    <p>Utiliza una contraseña larga y única para mantener protegida tu cuenta.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <x-input-label for="update_password_current_password" value="Contraseña actual" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>
            <div class="col-12 col-md-4">
                <x-input-label for="update_password_password" value="Contraseña nueva" />
                <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>
            <div class="col-12 col-md-4">
                <x-input-label for="update_password_password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 mt-4">
            <button class="btn btn-primary btn-sm px-3" type="submit">Actualizar contraseña</button>
            @if (session('status') === 'password-updated')
                <span class="text-success small" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>La contraseña se actualizó.</span>
            @endif
        </div>
    </form>
</section>
