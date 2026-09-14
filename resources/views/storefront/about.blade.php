@extends('layouts.storefront')

@section('title', 'À propos')
@section('seo_description', "MALKUTHAR SARL réunit trois métiers sous un même toit : produits digitaux, produits physiques et services, avec un programme d'affiliation à trois niveaux.")

@section('content')
@php
    $metiers = [
        ['Digital', 'Ebooks, formations, audios, programmes numériques. Livrés immédiatement après paiement, protégés par des liens temporaires.'],
        ['Physique', 'Livres, coffrets, accessoires. Gestion des stocks, des variantes et de la livraison suivie, du panier au colis.'],
        ['Service', 'Consultations, séances, accompagnements. Réservation d\'un créneau réel, en visioconférence ou en présentiel.'],
    ];
    $valeurs = [
        ['Immédiateté', 'Pour le digital, aucun délai : le lien de téléchargement part dès la confirmation du paiement.'],
        ['Fiabilité', 'Chaque paiement est revérifié côté serveur auprès du prestataire avant d\'être considéré comme définitif.'],
        ['Transparence', 'Pour les affiliés, tout mouvement est tracé dans un registre : commissions, retraits, ajustements.'],
    ];
@endphp

<section class="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">À propos</p>
    <h1 class="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
        Une maison, trois métiers.
    </h1>
    <p class="mt-4 max-w-xl text-ink-2">
        MALKUTHAR SARL est une plateforme de vente propriétaire, pensée pour commercialiser
        directement ses produits digitaux, ses produits physiques et ses services — sans dépendre
        de places de marché tierces. Une place de marché maison, ouverte à des praticiens et
        vendeurs sélectionnés de la région, est en préparation.
    </p>
</section>

<section class="border-y border-line bg-ground-alt">
    <div class="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-3">
        @foreach ($metiers as [$title, $text])
            <div class="bg-ground-alt px-4 py-8 sm:px-6">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">◇ {{ $title }}</p>
                <p class="mt-3 text-[15px] text-ink-2">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
    <x-ui.section-heading eyebrow="Notre approche" title="Ce à quoi nous tenons" />
    <div class="grid gap-8 sm:grid-cols-3">
        @foreach ($valeurs as [$title, $text])
            <div>
                <p class="text-[15px] font-semibold text-ink">{{ $title }}</p>
                <p class="mt-1 text-sm text-ink-2">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="border-t border-line bg-ground-alt">
    <div class="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold">Programme d'affiliation</p>
            <h2 class="mt-2 text-2xl font-bold tracking-[-0.01em] text-ink">Partagez, gagnez sur trois niveaux</h2>
            <p class="mt-3 text-ink-2">
                Réservé aux produits digitaux éligibles :
                <span class="font-semibold text-ink">15 % · 10 % · 5 %</span> répartis sur trois niveaux de parrainage.
                Lien personnel, tableau de bord et retraits déclenchés par l'affilié.
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-ui.button variant="secondary" :href="route('register')">Rejoindre le programme</x-ui.button>
                <x-ui.button variant="ghost" :href="route('affiliate.program-terms')">Lire les conditions →</x-ui.button>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-[1180px] px-4 py-12 text-center sm:px-6">
    <x-ui.button variant="primary" :href="route('catalog.index')">Explorer la boutique</x-ui.button>
</section>
@endsection
