@extends('layouts.app')

@section('title', 'Suivi de votre demande vendeur')

@section('content')
@php
    [$heading, $body, $tone, $badge] = match ($profile->status) {
        'pending' => [
            'Candidature en cours de validation',
            'Votre dossier est entre les mains de notre équipe. Chaque boutique est vérifiée manuellement sous 24 à 48 heures ouvrées.',
            'wait',
            'En attente d\'approbation',
        ],
        'suspended' => [
            'Boutique suspendue',
            $profile->suspension_reason ?: 'Votre espace vendeur a été temporairement suspendu par l\'administration.',
            'bad',
            'Suspendue',
        ],
        'closed' => [
            'Candidature non retenue',
            $profile->suspension_reason ?: 'Votre demande d\'ouverture de boutique n\'a pas pu être validée.',
            'bad',
            'Non retenue',
        ],
        default => ['Statut : '.$profile->status, '', 'neut', ucfirst((string) $profile->status)],
    };
    $toneCls = [
        'wait' => 'border-amber/25 bg-amber-tint text-amber',
        'bad'  => 'border-rust/25 bg-rust-tint text-rust',
        'neut' => 'border-line-strong bg-ground-alt text-ink-2',
    ][$tone];
    $isPending = $profile->status === 'pending';
@endphp

<div class="mx-auto max-w-2xl py-4 sm:py-8">
    <header>
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">{{ $heading }}</h1>
        <p class="mt-2 text-[15px] leading-relaxed text-ink-2">
            @if ($isPending)
                Merci pour votre demande. Les boutiques sont validées une par une par l'administrateur avant leur ouverture.
            @else
                Voici l'état actuel de votre espace vendeur.
            @endif
        </p>
    </header>

    {{-- Carte d'état --}}
    <section class="mt-8 rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-6" aria-labelledby="shop-name">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[13px] font-medium text-ink-3">Boutique</p>
                <h2 id="shop-name" class="mt-0.5 font-sans text-lg font-semibold text-ink">{{ $profile->display_name }}</h2>
                @if ($profile->legal_name)
                    <p class="text-sm text-ink-2">{{ $profile->legal_name }}</p>
                @endif
            </div>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $toneCls }}">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>{{ $badge }}
            </span>
        </div>

        @if ($body)
            <p class="mt-4 border-t border-line pt-4 text-sm leading-relaxed text-ink-2">{{ $body }}</p>
        @endif

        <dl class="mt-4 grid gap-4 border-t border-line pt-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-[13px] text-ink-3">Région d'exercice</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ $profile->region ?: '—' }} {{ $profile->city ? '('.$profile->city.')' : '' }}</dd>
            </div>
            <div>
                <dt class="text-[13px] text-ink-3">Date de transmission</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ $profile->created_at->translatedFormat('d F Y à H:i') }}</dd>
            </div>
        </dl>
    </section>

    {{-- Étapes --}}
    @if ($isPending)
        <section class="mt-6 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="steps-title">
            <h2 id="steps-title" class="font-sans text-lg font-semibold text-ink">Ce qui se passe ensuite</h2>

            <ol class="mt-5 space-y-0">
                <li class="relative flex gap-4 pb-6">
                    <span class="absolute left-[15px] top-8 bottom-0 w-px bg-line-strong" aria-hidden="true"></span>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-space-vendor text-white" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 8.5l3 3 6-7"/></svg>
                    </span>
                    <div class="pt-1">
                        <p class="text-sm font-semibold text-ink">Candidature transmise <span class="font-normal text-ink-3">(terminé)</span></p>
                        <p class="mt-0.5 text-sm leading-relaxed text-ink-2">Le profil de votre boutique est enregistré.</p>
                    </div>
                </li>

                <li class="relative flex gap-4 pb-6" aria-current="step">
                    <span class="absolute left-[15px] top-8 bottom-0 w-px bg-line-strong" aria-hidden="true"></span>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-space-vendor bg-space-vendor-soft text-sm font-semibold text-space-vendor" aria-hidden="true">2</span>
                    <div class="pt-1">
                        <p class="text-sm font-semibold text-ink">Examen manuel par l'administrateur <span class="font-normal text-space-vendor">(étape actuelle)</span></p>
                        <p class="mt-0.5 text-sm leading-relaxed text-ink-2">Un administrateur vérifie votre profil sous 24 à 48 h ouvrées. Vous recevrez un email dès l'approbation de votre compte.</p>
                    </div>
                </li>

                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-line-strong bg-surface text-sm font-semibold text-ink-3" aria-hidden="true">3</span>
                    <div class="pt-1">
                        <p class="text-sm font-semibold text-ink-2">Accès à l'espace vendeur et création de vos produits</p>
                        <p class="mt-0.5 text-sm leading-relaxed text-ink-3">Vous ajoutez vos fiches, configurez vos versements et soumettez chaque offre à la relecture avant publication.</p>
                    </div>
                </li>
            </ol>
        </section>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-2xl border border-line px-5 py-3 text-sm text-ink-2">
        <p>Une question ou un justificatif à compléter ?</p>
        <a href="{{ route('contact') }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">
            Contacter le support
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
</div>
@endsection
