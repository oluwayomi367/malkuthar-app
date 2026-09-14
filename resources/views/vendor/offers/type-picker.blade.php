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

<div class="mx-auto max-w-3xl">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
    <h1 class="quote mt-1 text-[26px] leading-tight text-ink sm:text-4xl">Quel type d'offre créez-vous ?</h1>
    <p class="mt-2 text-sm text-ink-2">Ce choix détermine les informations demandées. Il ne pourra pas être changé ensuite.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        @foreach ($types as $key => $t)
            <a href="{{ route('vendor.offers.create', ['type' => $key]) }}"
               class="group flex flex-col rounded-2xl border border-line bg-surface p-5 transition-all hover:-translate-y-0.5 hover:border-accent hover:shadow-[0_16px_40px_-24px_rgba(46,44,40,0.3)]">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $t['icon'] }}"/></svg>
                </span>
                <span class="mt-3 font-display text-[15px] font-semibold text-ink">{{ $t['label'] }}</span>
                <span class="mt-1 text-[13px] leading-snug text-ink-2">{{ $t['desc'] }}</span>
                <ul class="mt-3 space-y-1 text-[12px] text-ink-3">
                    @foreach ($t['points'] as $p)
                        <li class="flex items-center gap-1.5">
                            <svg class="h-3 w-3 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                            {{ $p }}
                        </li>
                    @endforeach
                </ul>
                <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-accent">Créer <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">→</span></span>
            </a>
        @endforeach
    </div>

    <p class="mt-6 text-sm"><a href="{{ route('vendor.offers.index') }}" class="text-ink-3 hover:text-ink">← Retour à mes offres</a></p>
</div>
@endsection
