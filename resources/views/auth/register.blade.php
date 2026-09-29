@extends('layouts.auth')

@section('title', 'Créer un compte Client')
@section('seo_description', 'Créez votre compte client MALKUTHAR pour suivre vos commandes, accéder à vos livres et réserver vos séances.')
@section('form_max', 'max-w-[580px]')

@section('auth_tagline', "Rejoignez la communauté MALKUTHAR.")
@section('auth_subtitle', "Accédez à vos commandes, vos téléchargements de livres et vos réservations en un instant.")
@section('auth_panel_extra')
    <div class="flex flex-wrap gap-2">
        @foreach (['Livres & Ebooks', 'Soins & Séances', 'Formations en ligne', 'Suivi de commandes'] as $t)
            <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">{{ $t }}</span>
        @endforeach
    </div>
@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Créer mon compte Client</h1>
<p class="mt-1.5 text-sm text-ink-2">Un espace personnel pour vos achats, téléchargements immédiats et réservations.</p>

@if ($errors->any())
    <div class="mt-5">
        <x-ui.alert tone="error">Veuillez vérifier les informations saisies dans les champs ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.client.store') }}" class="mt-6 grid gap-4">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
        <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
    </div>

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" :value="old('email')" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone" hint="Facultatif" autocomplete="tel" :value="old('phone')" />
        <x-ui.field name="country" label="Pays de résidence" hint="Facultatif" autocomplete="country-name" :value="old('country')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
        <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
    </div>

    <x-auth.referral-field />

    <div class="mt-2">
        <x-ui.button variant="primary" type="submit" :full="true">Créer mon compte Client</x-ui.button>
    </div>
</form>

<p class="mt-6 text-center text-sm text-ink-2">
    Déjà inscrit ?
    <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent">Se connecter</a>
</p>
@endsection
