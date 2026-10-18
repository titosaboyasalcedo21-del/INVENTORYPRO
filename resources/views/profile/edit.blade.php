<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">General</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mi perfil</li>
            </ol>
        </nav>
        <h1>Mi perfil</h1>
        <p>Administra tus datos de acceso y la seguridad de tu cuenta.</p>
    </x-slot>

    <div class="row g-3">
        <div class="col-12">@include('profile.partials.update-profile-information-form')</div>
        <div class="col-12">@include('profile.partials.update-password-form')</div>
        <div class="col-12">@include('profile.partials.delete-user-form')</div>
    </div>
</x-app-layout>
