@extends('layouts.app')

@section('title', 'Suivi de votre demande vendeur')

@section('content')
@php
    [$heading, $body, $box, $tone] = match ($profile->status) {
        'pending' => [
            'Candidature en cours de validation',
            'Votre dossier est actuellement entre les mains de notre équipe administrative. Chaque boutique est vérifiée manuellement sous 24 à 48 heures ouvrées.',
            'border-accent/30 bg-accent-soft/30',
            'accent',
        ],
        'suspended' => [
            'Boutique suspendue',
            $profile->suspension_reason ?: 'Votre espace vendeur a été temporairement suspendu par l\'administration.',
            'border-rust/30 bg-rust/[0.08]',
            'rust',
        ],
        'closed' => [
            'Candidature non retenue',
            $profile->suspension_reason ?: 'Votre demande d\'ouverture de boutique n\'a pas pu être validée.',
            'border-rust/30 bg-rust/[0.08]',
            'rust',
        ],
        default => ['Statut : '.$profile->status, '', 'border-line bg-surface', 'ink'],
    };
@endphp

<div class="mx-auto max-w-xl py-8">
    <div class="inline-flex items-center gap-2 rounded-full border border-accent/20 bg-surface px-3 py-1 text-xs font-semibold text-accent shadow-xs">
        <span>🛡️</span>
        <span>Validation manuelle par l'administrateur</span>
    </div>

    <h1 class="mt-3 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{{ $heading }}</h1>
    <p class="mt-2 text-sm text-ink-2">
        Merci de votre confiance. Nous veillons à maintenir une sélection d'offres authentiques et de haute qualité sur MALKUTHAR.
    </p>

    {{-- Carte d'état --}}
    <div class="mt-6 rounded-2xl border {{ $box }} p-6 shadow-xs">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wider text-ink-3 font-semibold">Boutique</p>
                <p class="mt-1 font-display text-lg font-bold text-ink">{{ $profile->display_name }}</p>
                @if ($profile->legal_name)
                    <p class="text-xs text-ink-2">{{ $profile->legal_name }}</p>
                @endif
            </div>
            <span class="inline-flex items-center rounded-full bg-surface px-3 py-1 text-xs font-bold capitalize text-{{ $tone }} shadow-xs border border-line">
                {{ $profile->status === 'pending' ? 'En attente d\'approbation' : $profile->status }}
            </span>
        </div>

        @if ($body)
            <p class="mt-4 text-xs leading-relaxed text-ink-2 border-t border-line/60 pt-3">{{ $body }}</p>
        @endif

        <dl class="mt-4 grid grid-cols-2 gap-3 text-xs border-t border-line/60 pt-3 text-ink-3">
            <div>
                <dt>Région d'exercice</dt>
                <dd class="font-semibold text-ink">{{ $profile->region ?: '—' }} {{ $profile->city ? '('.$profile->city.')' : '' }}</dd>
            </div>
            <div>
                <dt>Date de transmission</dt>
                <dd class="font-semibold text-ink">{{ $profile->created_at->translatedFormat('d F Y à H:i') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Timeline / Étapes --}}
    <div class="mt-8 rounded-2xl border border-line bg-surface p-6 shadow-xs">
        <p class="font-display text-xs font-bold uppercase tracking-wider text-ink">Les étapes de votre intégration</p>

        <ol class="mt-4 space-y-4">
            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-green/20 text-brand-green text-xs font-bold">
                    ✓
                </span>
                <div>
                    <p class="text-xs font-bold text-ink">Compte créé & Candidature transmise</p>
                    <p class="text-[11px] text-ink-3">Vos identifiants et le profil de votre boutique sont enregistrés dans notre système.</p>
                </div>
            </li>

            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-white text-xs font-bold animate-pulse">
                    2
                </span>
                <div>
                    <p class="text-xs font-bold text-ink">Examen manuel par l'administrateur (Étape actuelle)</p>
                    <p class="text-[11px] text-ink-2">Un administrateur vérifie manuellement votre profil sous 24 à 48h ouvrées. Vous recevrez un email dès l'approbation de votre compte.</p>
                </div>
            </li>

            <li class="flex items-start gap-3 opacity-60">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-line bg-ground text-ink-3 text-xs font-bold">
                    3
                </span>
                <div>
                    <p class="text-xs font-bold text-ink">Accès à l'espace vendeur & Création de vos produits</p>
                    <p class="text-[11px] text-ink-3">Une fois validé, vous accédez à votre espace pour ajouter vos fiches produits, configurer vos versements et soumettre chaque produit à modération.</p>
                </div>
            </li>
        </ol>
    </div>

    <div class="mt-6 flex items-center justify-between text-xs text-ink-2">
        <p>Une question ou un justificatif à compléter ?</p>
        <a href="{{ route('contact') }}" class="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">
            Contacter le support →
        </a>
    </div>
</div>
@endsection
