<section class="profile-card profile-danger" aria-labelledby="delete-account-heading">
    <h2 id="delete-account-heading">Eliminar cuenta</h2>
    <p>Esta acción es permanente. Confirma tu contraseña para eliminar tu cuenta personal.</p>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('¿Eliminar tu cuenta? Esta acción no se puede deshacer.');">
        @csrf
        @method('delete')
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <x-input-label for="delete_account_password" value="Confirma tu contraseña" />
                <x-text-input id="delete_account_password" name="password" type="password" autocomplete="current-password" required />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-danger btn-sm px-3" type="submit"><i class="bi bi-trash3 me-1" aria-hidden="true"></i>Eliminar cuenta</button>
            </div>
        </div>
    </form>
</section>
