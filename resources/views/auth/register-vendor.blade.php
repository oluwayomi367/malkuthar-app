@extends('layouts.auth')

@section('title', 'Inscription Vendeur — Créer mon compte')
@section('seo_description', 'Créez votre compte vendeur certifié sur MALKUTHAR Marketplace. Validation manuelle par l\'administrateur.')
@section('form_max', 'max-w-[640px]')

@section('auth_tagline', "Ouvrez votre espace vendeur sur MALKUTHAR.")
@section('auth_subtitle', "Un profil boutique certifié, un encaissement sécurisé et des versements transparents pour valoriser votre savoir-faire.")
@section('auth_panel_extra')
    <div class="flex flex-wrap gap-2">
        <span class="rounded-full border border-accent/40 bg-accent-soft/70 px-3 py-1.5 text-[13px] font-semibold text-accent">Compte certifié</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">Validation manuelle 24-48h</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">Tableau de bord dédié</span>
        <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">Versements automatisés</span>
    </div>
@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Créer mon compte Vendeur</h1>
<p class="mt-1.5 text-sm text-ink-2">Renseignez vos coordonnées personnelles et les informations de votre future boutique.</p>

{{-- Découplage explicite : Étape 1 = Compte Vendeur, Étape 2 (plus tard) = Création de produit --}}
<div class="mt-5 rounded-2xl border border-accent/30 bg-accent-soft/30 p-4 shadow-xs">
    <div class="flex items-start gap-3">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent text-white text-xs font-bold shadow-xs">
            1
        </span>
        <div class="text-xs">
            <p class="font-display font-bold uppercase tracking-wider text-ink">
                Étape 1 : Création de votre compte Vendeur uniquement
            </p>
            <p class="mt-1 leading-relaxed text-ink-2">
                Ce formulaire enregistre <span class="font-semibold text-ink">exclusivement vos identifiants d'accès et la présentation de votre boutique</span>. <strong class="text-ink">Vous n'avez aucun produit à enregistrer maintenant.</strong>
            </p>
            <p class="mt-2 leading-relaxed text-ink-2 border-t border-accent/20 pt-2">
                👉 <span class="font-semibold text-ink">Étape 2 (Ultérieure) :</span> La création, la description et la mise en vente de vos produits s'effectueront séparément depuis votre espace vendeur, une fois votre compte examiné et validé manuellement par notre équipe administrative (sous 24h à 48h).
            </p>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="mt-5">
        <x-ui.alert tone="error">Veuillez corriger les erreurs indiquées ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.vendor.store') }}" class="mt-6 grid gap-5">
    @csrf

    {{-- Section 1 : Compte personnel --}}
    <div class="rounded-2xl border border-line bg-surface/50 p-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-accent">1. Vos identifiants de connexion</p>
        <div class="mt-3 grid gap-3.5">
            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
                <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
            </div>

            <x-ui.field name="email" label="Adresse email professionnelle" type="email" :required="true" autocomplete="email" :value="old('email')" />

            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-ui.field name="phone" label="Téléphone professionnel" :required="true" autocomplete="tel" :value="old('phone')"
                            placeholder="Ex. : +229 97 00 00 00" />
                <x-ui.field name="country" label="Pays" :required="true" autocomplete="country-name" :value="old('country', 'Bénin')" />
            </div>

            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
                <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
            </div>

            <x-auth.referral-field />
        </div>
    </div>

    {{-- Section 2 : Profil de la Boutique --}}
    <div class="rounded-2xl border border-line bg-surface/50 p-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-accent">2. Votre Secteur & Boutique</p>
        <div class="mt-3 grid gap-3.5">
            <div>
                <label class="block text-xs font-semibold text-ink">
                    Secteur d'activité principal <span class="text-rust">*</span>
                </label>
                <p class="text-[11.5px] text-ink-3">Détermine l'espace dédié dans lequel vos offres seront répertoriées.</p>
                <div class="mt-2.5 grid gap-2.5 sm:grid-cols-2">
                    <label class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3 transition-colors hover:border-accent hover:bg-ground-alt/40 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/20">
                        <input type="radio" name="business_sector" value="fashion_beauty" @checked(old('business_sector', 'fashion_beauty') === 'fashion_beauty')
                               class="mt-1 h-4 w-4 border-line-strong text-accent focus:ring-accent/30">
                        <div class="text-xs">
                            <span class="font-bold text-ink flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </span>
                                Mode, Beauté & Cosmétiques
                            </span>
                            <p class="mt-1 text-[11.5px] text-ink-2 leading-relaxed">
                                Vêtements, cosmétiques & soins, chaussures, maroquinerie, accessoires & artisanat.
                            </p>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3 transition-colors hover:border-accent hover:bg-ground-alt/40 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/20">
                        <input type="radio" name="business_sector" value="real_estate_hospitality" @checked(old('business_sector') === 'real_estate_hospitality')
                               class="mt-1 h-4 w-4 border-line-strong text-accent focus:ring-accent/30">
                        <div class="text-xs">
                            <span class="font-bold text-ink flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </span>
                                Immobilier & Guest Houses
                            </span>
                            <p class="mt-1 text-[11.5px] text-ink-2 leading-relaxed">
                                Résidences meublées, guest houses de charme, agences immobilières (villas & séjours).
                            </p>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3 transition-colors hover:border-accent hover:bg-ground-alt/40 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/20">
                        <input type="radio" name="business_sector" value="services_wellness" @checked(old('business_sector') === 'services_wellness')
                               class="mt-1 h-4 w-4 border-line-strong text-accent focus:ring-accent/30">
                        <div class="text-xs">
                            <span class="font-bold text-ink flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                Services & Praticiens
                            </span>
                            <p class="mt-1 text-[11.5px] text-ink-2 leading-relaxed">
                                Consultations professionnelles, thérapies, soins bien-être & coaching avec prise de rendez-vous.
                            </p>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3 transition-colors hover:border-accent hover:bg-ground-alt/40 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/20">
                        <input type="radio" name="business_sector" value="digital_academy" @checked(old('business_sector') === 'digital_academy')
                               class="mt-1 h-4 w-4 border-line-strong text-accent focus:ring-accent/30">
                        <div class="text-xs">
                            <span class="font-bold text-ink flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
                                    </svg>
                                </span>
                                Savoirs & Formations
                            </span>
                            <p class="mt-1 text-[11.5px] text-ink-2 leading-relaxed">
                                Ebooks, cours en ligne, masterclasses vidéo, audios et supports pédagogiques.
                            </p>
                        </div>
                    </label>
                </div>
                @error('business_sector')<p class="mt-1.5 text-xs text-rust font-medium">{{ $message }}</p>@enderror
            </div>

            <x-ui.field name="display_name" label="Nom public de la boutique / enseigne" :required="true" :value="old('display_name')"
                        placeholder="Ex. : Maison de Couture Ébène, Résidence Les Palmiers..." />

            <x-ui.field name="legal_name" label="Raison sociale ou nom d'entreprise (facultatif)" :value="old('legal_name')"
                        placeholder="Si vous disposez d'un RCCM ou statut d'entreprise enregistré" />

            <x-ui.textarea name="bio" label="Présentation de votre activité & vos offres" :rows="3" :required="true"
                           :value="old('bio')"
                           placeholder="Décrivez ce que vous proposez (produits artisanaux, formations, soins, consultations...), votre expérience et votre savoir-faire." />

            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-ui.field name="region" label="Région / Département" :required="true" :value="old('region')"
                            placeholder="Ex. : Littoral" />
                <x-ui.field name="city" label="Ville d'exercice" :value="old('city')"
                            placeholder="Ex. : Cotonou" />
            </div>
        </div>
    </div>

    {{-- Acceptation des conditions --}}
    <div class="rounded-xl border border-line/80 bg-ground-alt/70 p-3.5">
        <label class="flex items-start gap-3 text-xs text-ink-2 cursor-pointer">
            <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms'))
                   class="mt-0.5 h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
            <span>
                J'accepte les <a href="{{ route('marketplace.vendor-terms') }}" target="_blank" class="font-semibold text-accent underline underline-offset-2">conditions générales du programme vendeur</a>
                et je comprends que l'ouverture de mon compte ainsi que chaque produit publié sont soumis à une validation manuelle par l'administrateur.
            </span>
        </label>
        @error('accept_terms')<p class="mt-1.5 text-xs text-rust font-medium">{{ $message }}</p>@enderror
    </div>

    <div class="mt-1">
        <x-ui.button variant="primary" type="submit" :full="true">
            Créer mon compte Vendeur
        </x-ui.button>
    </div>
</form>

<div class="mt-6 flex flex-col items-center gap-2 text-center text-xs text-ink-2">
    <p>
        Vous avez déjà un compte client MALKUTHAR ?
        <a href="{{ route('vendor.application') }}" class="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">
            Faire une demande vendeur avec mon compte existant →
        </a>
    </p>
    <p>
        Déjà vendeur ?
        <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 hover:text-accent">
            Se connecter
        </a>
    </p>
</div>
@endsection
