@extends('layouts.auth')

@section('title', 'Créer un compte')
@section('seo_description', 'Créez votre compte MALKUTHAR — un seul compte pour acheter et, si vous le souhaitez, devenir affilié.')
@section('form_max', 'max-w-[620px]')

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink">Créer mon compte</h1>
<p class="mt-1.5 text-sm text-ink-2">Un seul compte pour acheter et, si vous le voulez, devenir affilié.</p>

@if ($errors->any())
    <div class="mt-5">
        <x-ui.alert tone="error">Vérifiez les champs en surbrillance ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register') }}" class="mt-6 grid gap-4">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus />
        <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" />
    </div>

    <x-ui.field name="email" label="Email" type="email" :required="true" autocomplete="email" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone" hint="Facultatif" autocomplete="tel" />
        <x-ui.field name="country" label="Pays" hint="Facultatif" autocomplete="country-name" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
        <x-ui.field name="password_confirmation" label="Confirmer" type="password" :required="true" autocomplete="new-password" />
    </div>

    <x-ui.field
        name="referral_code"
        label="Code de parrainage"
        hint="Pré-rempli si vous êtes arrivé par un lien d'affilié"
        :value="request()->cookie(\App\Support\ReferralCookie::NAME)"
    />

    <x-ui.button variant="primary" type="submit" :full="true">Créer mon compte</x-ui.button>
</form>

<p class="mt-6 text-center text-sm text-ink-2">
    Déjà inscrit ?
    <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent">Se connecter</a>
</p>
@endsection
