@extends('layouts.auth')

@section('title', 'Inscription Vendeur — Créer mon compte')
@section('seo_description', 'Créez votre compte vendeur certifié sur MALKUTHAR Marketplace. Validation manuelle par l\'administrateur.')
@section('form_max', 'max-w-[620px]')

@section('auth_heading', 'Créer mon compte vendeur')
@section('auth_intro', 'Renseignez vos coordonnées puis les informations de votre future boutique.')
@section('auth_tagline', 'Vendez sur la place de marché MALKUTHAR.')
@section('auth_subtitle', "Créez votre compte et présentez votre activité. Notre équipe examine chaque demande sous 24 à 48 heures avant l'ouverture de votre boutique.")
@section('auth_image', 'auth.register_vendor')

@php
    $sectors = [
        'fashion_beauty' => [
            'Mode, Beauté & Cosmétiques',
            'Vêtements, cosmétiques et soins, chaussures, maroquinerie, accessoires, artisanat.',
            'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        ],
        'real_estate_hospitality' => [
            'Immobilier & Guest Houses',
            'Résidences meublées, guest houses, agences immobilières (villas et séjours).',
            'M3 11l9-7 9 7M5 9.5V20h5v-6h4v6h5V9.5',
        ],
        'services_wellness' => [
            'Services & Praticiens',
            'Consultations, thérapies, soins bien-être et coaching avec prise de rendez-vous.',
            'M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M4 7h16v12H4zM4 12h16',
        ],
        'digital_academy' => [
            'Savoirs & Formations',
            'Ebooks, cours en ligne, masterclasses vidéo, audios et supports pédagogiques.',
            'M12 14l9-5-9-5-9 5 9 5zm0 0v6m-6-8.5v4.5c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5',
        ],
    ];
    $selectedSector = old('business_sector', 'fashion_beauty');
@endphp

@section('content')

{{-- Découplage explicite : Étape 1 = Compte Vendeur, Étape 2 (plus tard) = Création de produit --}}
<div class="rounded-2xl bg-ground-alt p-5">
    <h2 class="font-sans text-base font-semibold tracking-normal text-ink">Étape 1 : Création de votre compte Vendeur uniquement</h2>
    <p class="mt-2 text-sm leading-relaxed text-ink-2">
        Ce formulaire enregistre vos identifiants d'accès et la présentation de votre boutique.
        <strong class="font-semibold text-ink">Vous n'avez aucun produit à enregistrer maintenant.</strong>
    </p>
    <p class="mt-3 border-t border-line pt-3 text-sm leading-relaxed text-ink-2">
        <span class="font-semibold text-ink">Étape 2, plus tard :</span> vous créerez et mettrez en vente vos produits depuis votre espace vendeur, une fois votre compte examiné et validé par notre équipe (sous 24 à 48 heures).
    </p>
</div>

@if ($errors->any())
    <div class="mt-6">
        <x-ui.alert tone="error">Veuillez corriger les erreurs indiquées ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.vendor.store') }}" class="mt-8 grid gap-10">
    @csrf

    {{-- Section 1 : Compte personnel --}}
    <fieldset class="grid gap-5">
        <legend class="mb-5 w-full border-b border-line pb-3 text-base font-semibold text-ink">1. Vos identifiants de connexion</legend>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
            <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
        </div>

        <x-ui.field name="email" label="Adresse email professionnelle" type="email" :required="true" autocomplete="email" :value="old('email')" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="phone" label="Téléphone professionnel" :required="true" autocomplete="tel" :value="old('phone')"
                        placeholder="Ex. : +229 97 00 00 00" />
            <x-ui.field name="country" label="Pays" :required="true" autocomplete="country-name" :value="old('country', 'Bénin')" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
            <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
        </div>

        <x-auth.referral-field />
    </fieldset>

    {{-- Section 2 : Profil de la Boutique --}}
    <fieldset class="grid gap-5">
        <legend class="mb-5 w-full border-b border-line pb-3 text-base font-semibold text-ink">2. Votre secteur et votre boutique</legend>

        <fieldset>
            <legend class="text-sm font-semibold text-ink">
                Secteur d'activité principal <span class="text-accent" aria-hidden="true">*</span>
            </legend>
            <p class="mt-1 text-sm text-ink-3">Détermine l'espace dans lequel vos offres seront répertoriées.</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($sectors as $value => [$label, $desc, $icon])
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface p-4 transition-colors hover:border-line-strong has-[:checked]:border-accent has-[:checked]:bg-accent-soft/40">
                        <input type="radio" name="business_sector" value="{{ $value }}" @checked($selectedSector === $value)
                               class="mt-0.5 h-5 w-5 shrink-0 border-line-strong text-accent focus:ring-accent/30">
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <svg class="h-4 w-4 shrink-0 text-accent-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                                {{ $label }}
                            </span>
                            <span class="mt-1 block text-[13px] leading-relaxed text-ink-2">{{ $desc }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('business_sector')<p class="mt-2 text-sm font-medium text-rust">{{ $message }}</p>@enderror
        </fieldset>

        <x-ui.field name="display_name" label="Nom public de la boutique / enseigne" :required="true" :value="old('display_name')"
                    placeholder="Ex. : Maison de Couture Ébène, Résidence Les Palmiers..." />

        <x-ui.field name="legal_name" label="Raison sociale ou nom d'entreprise (facultatif)" :value="old('legal_name')"
                    placeholder="Si vous disposez d'un RCCM ou statut d'entreprise enregistré" />

        <x-ui.textarea name="bio" label="Présentation de votre activité & vos offres" :rows="4" :required="true"
                       :value="old('bio')"
                       placeholder="Décrivez ce que vous proposez (produits artisanaux, formations, soins, consultations...), votre expérience et votre savoir-faire." />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="region" label="Région / Département" :required="true" :value="old('region')"
                        placeholder="Ex. : Littoral" />
            <x-ui.field name="city" label="Ville d'exercice" :value="old('city')"
                        placeholder="Ex. : Cotonou" />
        </div>
    </fieldset>

    {{-- Acceptation des conditions --}}
    <div class="grid gap-5">
        <div>
            <label class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-ink-2">
                <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms'))
                       class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-strong text-accent focus:ring-accent/30">
                <span>
                    J'accepte les <a href="{{ route('marketplace.vendor-terms') }}" target="_blank" class="font-semibold text-ink underline decoration-accent underline-offset-2 hover:text-accent-2">conditions générales du programme vendeur</a>
                    et je comprends que l'ouverture de mon compte ainsi que chaque produit publié sont soumis à une validation manuelle par l'administrateur.
                </span>
            </label>
            @error('accept_terms')<p class="mt-2 text-sm font-medium text-rust">{{ $message }}</p>@enderror
        </div>

        <x-ui.button variant="primary" type="submit" :full="true">
            Créer mon compte Vendeur
        </x-ui.button>
    </div>
</form>

<div class="mt-8 space-y-2 border-t border-line pt-6 text-sm text-ink-2">
    <p>
        Vous avez déjà un compte client MALKUTHAR ?
        <a href="{{ route('vendor.application') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 hover:text-accent-2">Faire une demande vendeur avec mon compte existant</a>
    </p>
</div>
@endsection

@section('auth_footer')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-surface/80 px-4 text-xs font-semibold text-ink shadow-2xs transition-colors hover:border-line-strong hover:bg-surface">
        <span class="text-accent" aria-hidden="true">✦</span> Déjà vendeur ? Se connecter
    </a>
@endsection
