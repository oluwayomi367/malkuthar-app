@extends('layouts.app')

@section('title', 'Nouvelle offre')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $types = [
        'physical' => [
            'label' => 'Produit physique',
            'desc' => 'Objets, coffrets, ouvrages. Stock, variantes (couleur, taille) et livraison suivie.',
            'icon' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10',
            'points' => ['Gestion du stock et des variantes', 'Poids et dimensions', 'Livraison suivie'],
        ],
        'digital' => [
            'label' => 'Produit digital',
            'desc' => 'Ebooks, gabarits, fichiers audio. Livraison automatique par lien sécurisé après paiement.',
            'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4',
            'points' => ['Livraison immédiate', 'Fichier ou lien externe', 'Quota de téléchargement'],
        ],
        'service' => [
            'label' => 'Service',
            'desc' => 'Consultations, séances, accompagnements. Réservation d\'un créneau réel, en visio ou sur place.',
            'icon' => 'M12 8v4l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
            'points' => ['Durée et créneaux', 'En ligne ou sur place', 'Confirmation auto ou manuelle'],
        ],
        'training' => [
            'label' => 'Formation',
            'desc' => 'Parcours pédagogiques, en autonomie ou en cohorte, avec suivi de progression.',
            'icon' => 'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5',
            'points' => ['En autonomie ou en direct', 'Niveau et langue', 'Accès limité dans le temps'],
        ],
    ];
@endphp

<div class="mx-auto max-w-4xl">
    <a href="{{ route('vendor.offers.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Mes offres
    </a>
    <header class="mt-2">
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Nouvelle offre</h1>
        <p class="mt-2 text-[15px] leading-relaxed text-ink-2">Quel type d'offre créez-vous ? Ce choix détermine les informations demandées. Il ne pourra pas être changé ensuite.</p>
    </header>

    <ul class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach ($types as $key => $t)
            <li>
                <a href="{{ route('vendor.offers.create', ['type' => $key]) }}"
                   class="group flex h-full min-h-11 flex-col rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(28,25,23,0.04)] transition-colors hover:border-space-vendor focus-visible:border-space-vendor sm:p-6">
                    <span class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-space-vendor-soft text-space-vendor">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $t['icon'] }}"/></svg>
                        </span>
                        <span class="text-lg font-semibold text-ink">{{ $t['label'] }}</span>
                    </span>
                    <span class="mt-3 text-sm leading-relaxed text-ink-2">{{ $t['desc'] }}</span>
                    <ul class="mt-4 space-y-1.5 border-t border-line pt-4 text-[13px] text-ink-2">
                        @foreach ($t['points'] as $p)
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-space-vendor" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7"/></svg>
                                {{ $p }}
                            </li>
                        @endforeach
                    </ul>
                    <span class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-space-vendor">
                        Créer un {{ $key === 'training' ? 'parcours' : ($key === 'service' ? 'service' : 'produit') }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
