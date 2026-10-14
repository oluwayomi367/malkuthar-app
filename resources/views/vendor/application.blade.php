@extends('layouts.auth')

@section('title', 'Devenir vendeur')
@section('form_max', 'max-w-xl')

@section('auth_tagline', "Créez et vendez vos offres.")
@section('auth_subtitle', "Praticiens, créateurs, formateurs, artisans : votre boutique, sous votre nom, sur MALKUTHAR.")
@section('auth_panel_extra')
    <div class="flex flex-wrap gap-2">
        @foreach (['Produits physiques', 'Produits digitaux', 'Services', 'Formations'] as $t)
            <span class="rounded-full border border-night-ink-2/40 px-3 py-1.5 text-[13px] font-medium text-night-ink">{{ $t }}</span>
        @endforeach
    </div>
@endsection

@section('content')
<h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[36px]">Ouvrir un espace vendeur</h1>
<p class="mt-2 text-[15px] leading-relaxed text-ink-2">
    Votre compte MALKUTHAR devient aussi votre compte vendeur. Aucun second compte n'est nécessaire.
</p>

{{-- Ce qui se passe --}}
<section class="mt-6 rounded-2xl border border-line bg-surface p-5" aria-labelledby="steps-title">
    <h2 id="steps-title" class="font-sans text-base font-semibold text-ink">Étape 1 : Demande d'activation de votre espace vendeur</h2>
    <p class="mt-1.5 text-sm leading-relaxed text-ink-2">
        Ce formulaire sert uniquement à renseigner l'identité de votre boutique. <strong class="font-semibold text-ink">Vous n'avez aucun produit à saisir maintenant.</strong>
    </p>
    <ol class="mt-4 space-y-3 border-t border-line pt-4 text-sm">
        <li class="flex items-start gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-space-vendor text-xs font-semibold text-white" aria-hidden="true">1</span>
            <span class="text-ink"><span class="sr-only">Étape 1 : </span>Vous présentez votre boutique (aujourd'hui).</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-line-strong text-xs font-semibold text-ink-2" aria-hidden="true">2</span>
            <span class="text-ink-2"><span class="sr-only">Étape 2 : </span>Un administrateur vérifie votre demande manuellement, sous 24 h à 48 h.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-line-strong text-xs font-semibold text-ink-2" aria-hidden="true">3</span>
            <span class="text-ink-2"><span class="sr-only">Étape 3 : </span>Une fois validée, vous créez vos produits depuis votre espace vendeur.</span>
        </li>
    </ol>
</section>

<form method="POST" action="{{ route('vendor.application.store') }}" class="mt-8 grid gap-5">
    @csrf

    <x-ui.field name="display_name" label="Nom de votre boutique" :required="true"
                :value="old('display_name')" placeholder="Ex. : Atelier Lumière" />

    <x-ui.field name="legal_name" label="Raison sociale (facultatif)" :value="old('legal_name')" />

    <x-ui.textarea name="bio" label="Votre activité en quelques phrases" :rows="4" :required="true"
                   placeholder="Ce que vous proposez, votre approche, votre expérience." />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="region" label="Région" :required="true" :value="old('region')"
                    placeholder="Ex. : Littoral" />
        <x-ui.field name="city" label="Ville (facultatif)" :value="old('city')" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="contact_email" label="Email de contact (facultatif)" type="email"
                    :value="old('contact_email', auth()->user()->email)" />
        <x-ui.field name="phone" label="Téléphone (facultatif)" :value="old('phone', auth()->user()->phone)" />
    </div>

    <div class="border-t border-line pt-5">
        <label class="flex items-start gap-3 text-sm leading-relaxed text-ink-2">
            <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms'))
                   @error('accept_terms') aria-invalid="true" aria-describedby="accept_terms-error" @enderror
                   class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-strong text-space-vendor focus:ring-space-vendor/30">
            <span>
                J'accepte les <a href="{{ route('marketplace.vendor-terms') }}" class="font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">conditions du programme vendeur</a>
                et je comprends que chaque offre est validée avant publication.
            </span>
        </label>
        @error('accept_terms')<p id="accept_terms-error" class="mt-1.5 text-[13px] font-medium text-rust">{{ $message }}</p>@enderror
    </div>

    <div>
        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">
            Envoyer ma demande
        </button>
    </div>
</form>
@endsection
