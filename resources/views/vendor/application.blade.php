@extends('layouts.auth')

@section('title', 'Devenir vendeur')
@section('form_max', 'max-w-xl')

@section('auth_tagline', "Créez et vendez vos offres.")
@section('auth_subtitle', "Praticiens, créateurs, formateurs, artisans — votre boutique, sous votre nom, sur MALKUTHAR.")
@section('auth_panel_extra')
    <div class="flex flex-wrap gap-2">
        @foreach (['Produits physiques', 'Produits digitaux', 'Services', 'Formations'] as $t)
            <span class="rounded-full border border-line-strong bg-surface/70 px-3 py-1.5 text-[13px] font-medium text-ink">{{ $t }}</span>
        @endforeach
    </div>
@endsection

@section('content')
<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Ouvrir un espace vendeur</h1>
<p class="mt-2 text-sm text-ink-2">
    Votre compte MALKUTHAR devient aussi votre compte vendeur — aucun second compte.
</p>

<div class="mt-4 rounded-xl border border-accent/25 bg-accent-soft/40 p-4 text-xs text-ink-2">
    <div class="flex items-start gap-2.5">
        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-accent text-white text-[10px] font-bold">1</span>
        <div>
            <p class="font-semibold text-ink">Étape 1 : Demande d'activation de votre espace vendeur</p>
            <p class="mt-0.5 leading-relaxed">
                Ce formulaire sert uniquement à renseigner l'identité de votre boutique. <strong class="text-ink">Vous n'avez aucun produit à saisir maintenant.</strong>
            </p>
            <p class="mt-1.5 border-t border-accent/20 pt-1.5 text-ink-3">
                👉 Vos produits seront créés séparément depuis votre espace vendeur, une fois cette demande validée manuellement par l'administrateur (24h à 48h).
            </p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('vendor.application.store') }}" class="mt-6 grid gap-4">
    @csrf

    <x-ui.field name="display_name" label="Nom de votre boutique" :required="true"
                :value="old('display_name')" placeholder="Ex. : Atelier Lumière" />

    <x-ui.field name="legal_name" label="Raison sociale (facultatif)" :value="old('legal_name')" />

    <x-ui.textarea name="bio" label="Votre activité en quelques phrases" :rows="4" :required="true"
                   placeholder="Ce que vous proposez, votre approche, votre expérience." />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="region" label="Région" :required="true" :value="old('region')"
                    placeholder="Ex. : Littoral" />
        <x-ui.field name="city" label="Ville (facultatif)" :value="old('city')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="contact_email" label="Email de contact (facultatif)" type="email"
                    :value="old('contact_email', auth()->user()->email)" />
        <x-ui.field name="phone" label="Téléphone (facultatif)" :value="old('phone', auth()->user()->phone)" />
    </div>

    <label class="mt-1 flex items-start gap-2.5 text-sm text-ink-2">
        <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms'))
               class="mt-0.5 h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
        <span>
            J'accepte les <a href="{{ route('marketplace.vendor-terms') }}" class="text-accent hover:underline">conditions du programme vendeur</a>
            et je comprends que chaque offre est validée avant publication.
        </span>
    </label>
    @error('accept_terms')<p class="text-xs text-rust">{{ $message }}</p>@enderror

    <div class="mt-2">
        <x-ui.button type="submit" variant="primary" :full="true">Envoyer ma demande</x-ui.button>
    </div>
</form>
@endsection
