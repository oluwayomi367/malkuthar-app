@extends('layouts.auth')

@section('title', 'Devenir Affilié — Inscription')
@section('seo_description', 'Inscrivez-vous au programme d\'affiliation MALKUTHAR et touchez des commissions sur 3 niveaux : 15 %, 10 % et 5 % sur chaque vente.')
@section('form_max', 'max-w-[580px]')

@section('auth_tagline', "Partagez l'éveil, percevez des gains durables.")
@section('auth_subtitle', "Recommandez les ouvrages, séances et formations et percevez jusqu'à 30 % de commissions réparties sur 3 niveaux.")
@section('auth_panel_extra')
    <div class="flex flex-wrap gap-2">
        <span class="rounded-full border border-accent/40 bg-accent-soft/70 px-3 py-1.5 text-[13px] font-semibold text-accent">15 % Niveau 1</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">10 % Niveau 2</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">5 % Niveau 3</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">Retraits rapides</span>
    </div>
@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Devenir Partenaire Affilié</h1>
<p class="mt-1.5 text-sm text-ink-2">Obtenez votre lien de parrainage unique et suivez vos gains en temps réel depuis votre espace dédié.</p>

{{-- Encart explicatif 3 niveaux & parrainage système --}}
<div class="mt-5 rounded-2xl border border-accent/25 bg-accent-soft/40 p-4">
    <div class="flex items-center gap-2">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent text-white text-xs font-bold">★</span>
        <p class="font-display text-xs font-bold uppercase tracking-wider text-ink">Commissions garanties sur 3 niveaux</p>
    </div>
    <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded-xl border border-accent/30 bg-surface/80 p-2 shadow-xs">
            <span class="block font-bold text-accent text-base">15 %</span>
            <span class="text-ink-2 text-[11px]">Niveau 1 (Direct)</span>
        </div>
        <div class="rounded-xl border border-line bg-surface/80 p-2 shadow-xs">
            <span class="block font-bold text-ink text-base">10 %</span>
            <span class="text-ink-2 text-[11px]">Niveau 2</span>
        </div>
        <div class="rounded-xl border border-line bg-surface/80 p-2 shadow-xs">
            <span class="block font-bold text-ink text-base">5 %</span>
            <span class="text-ink-2 text-[11px]">Niveau 3</span>
        </div>
    </div>
    <p class="mt-2.5 text-[11px] leading-relaxed text-ink-2">
        <span class="font-semibold text-ink">Parrainage équitable :</span> Si vous n'avez pas de parrain, le Système MALKUTHAR devient votre parrain d'office pour vous intégrer immédiatement au réseau.
    </p>
</div>

@if ($errors->any())
    <div class="mt-5">
        <x-ui.alert tone="error">Veuillez vérifier les informations renseignées ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.affiliate.store') }}" class="mt-6 grid gap-4">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
        <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
    </div>

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" :value="old('email')" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone (WhatsApp / Retraits)" :required="true" autocomplete="tel" :value="old('phone')"
                    hint="Utilisé pour les versements Mobile Money" />
        <x-ui.field name="country" label="Pays de résidence" :required="true" autocomplete="country-name" :value="old('country', 'Bénin')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
        <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
    </div>

    <x-auth.referral-field />

    <div class="mt-2">
        <x-ui.button variant="primary" type="submit" :full="true">Valider mon inscription et obtenir mon lien</x-ui.button>
    </div>
</form>

<p class="mt-6 text-center text-sm text-ink-2">
    Déjà inscrit ?
    <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent">Se connecter</a>
</p>
@endsection
