@extends('layouts.app')

@section('title', 'Votre demande vendeur')

@section('content')
@php
    [$heading, $body, $box] = match ($profile->status) {
        'pending' => [
            'En cours de vérification',
            'Nous examinons votre demande. Vous serez notifié·e dès qu\'une décision est prise.',
            'border-amber/30 bg-amber/[0.08]',
        ],
        'suspended' => [
            'Espace suspendu',
            $profile->suspension_reason ?: 'Votre espace vendeur est suspendu.',
            'border-rust/30 bg-rust/[0.08]',
        ],
        'closed' => [
            'Demande non retenue',
            $profile->suspension_reason ?: 'Votre demande n\'a pas été retenue.',
            'border-rust/30 bg-rust/[0.08]',
        ],
        default => ['Statut : '.$profile->status, '', 'border-line bg-surface'],
    };
@endphp
<div class="mx-auto max-w-lg">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
    <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{{ $heading }}</h1>

    <div class="mt-5 rounded-2xl border {{ $box }} p-5 text-sm text-ink-2">
        <p class="font-medium text-ink">Boutique : {{ $profile->display_name }}</p>
        @if ($body)<p class="mt-2">{{ $body }}</p>@endif
        <p class="mt-2 text-xs text-ink-3">Demande envoyée le {{ $profile->created_at->translatedFormat('d F Y') }}.</p>
    </div>

    <p class="mt-4 text-sm text-ink-2">
        Une question ? <a href="{{ route('contact') }}" class="text-accent hover:underline">Contactez l'équipe</a>.
    </p>
</div>
@endsection
