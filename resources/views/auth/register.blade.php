@extends('layouts.auth')

@section('title', 'Créer un compte Client')
@section('seo_description', 'Créez votre compte client MALKUTHAR pour suivre vos commandes, accéder à vos livres et réserver vos séances.')
@section('form_max', 'max-w-[560px]')

@section('auth_heading', 'Créer mon compte')
@section('auth_intro', "Quelques informations suffisent. Les champs marqués d'un astérisque sont obligatoires.")
@section('auth_tagline', 'Votre espace client.')
@section('auth_subtitle', 'Suivez vos commandes, retrouvez vos livres numériques et gérez vos réservations depuis un même endroit.')
@section('auth_image', 'auth.register')

@section('content')
@if ($errors->any())
    <div class="mb-5">
        <x-ui.alert tone="error">Veuillez vérifier les informations saisies dans les champs ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.client.store') }}" class="grid gap-5">
    @csrf

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
        <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
    </div>

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" :value="old('email')" />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone" hint="Facultatif" autocomplete="tel" :value="old('phone')" />
        <x-ui.field name="country" label="Pays de résidence" hint="Facultatif" autocomplete="country-name" :value="old('country')" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
        <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
    </div>

    <x-auth.referral-field />

    <div class="mt-1">
        <x-ui.button variant="primary" type="submit" :full="true">Créer mon compte Client</x-ui.button>
    </div>
</form>

@endsection

@section('auth_footer')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-surface/80 px-4 text-xs font-semibold text-ink shadow-2xs transition-colors hover:border-line-strong hover:bg-surface">
        <span class="text-accent" aria-hidden="true">✦</span> Déjà inscrit ? Se connecter
    </a>
@endsection
