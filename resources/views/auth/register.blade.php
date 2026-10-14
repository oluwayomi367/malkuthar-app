@extends('layouts.auth')

@section('title', 'Créer un compte Client')
@section('seo_description', 'Créez votre compte client MALKUTHAR pour suivre vos commandes, accéder à vos livres et réserver vos séances.')
@section('form_max', 'max-w-[560px]')

@section('auth_tagline', 'Votre espace client.')
@section('auth_subtitle', 'Suivez vos commandes, retrouvez vos livres numériques et gérez vos réservations depuis un même endroit.')
@section('auth_panel_extra')
    <ul class="divide-y divide-night-line border-y border-night-line text-sm text-night-ink">
        <li class="py-3">Téléchargement des produits digitaux dès le paiement</li>
        <li class="py-3">Historique et suivi de vos commandes</li>
        <li class="py-3">Réservation des séances et consultations</li>
    </ul>
@endsection

@section('content')
<h1 class="font-display text-[32px] font-semibold leading-tight text-ink">Créer mon compte Client</h1>
<p class="mt-2 text-[15px] text-ink-2">Quelques informations suffisent. Les champs marqués d'un astérisque sont obligatoires.</p>

@if ($errors->any())
    <div class="mt-6">
        <x-ui.alert tone="error">Veuillez vérifier les informations saisies dans les champs ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.client.store') }}" class="mt-8 grid gap-5">
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

<div class="mt-8 border-t border-line pt-6 text-sm text-ink-2">
    <p>
        Déjà inscrit ?
        <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent-2">Se connecter</a>
    </p>
</div>
@endsection
