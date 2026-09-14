@extends('layouts.app')

@section('title', 'Mes rendez-vous')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes rendez-vous</h1>
<p class="mt-1 text-sm text-ink-2">Vos prestations réservées, à venir et passées.</p>

<section class="mt-8">
    <h2 class="mb-3 font-display text-sm font-semibold uppercase tracking-wide text-ink-2">À venir</h2>
    @forelse ($upcoming as $item)
        <div class="mb-3 rounded-2xl border border-black/10 bg-surface p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[15px] font-semibold text-ink">{{ $item->product_name }}</p>
                    <p class="mt-1 text-sm text-accent">{{ $item->reservation_datetime->translatedFormat('l d F Y · H:i') }}</p>
                    <p class="mt-1 text-xs text-ink-3">
                        @if ($item->product?->delivery_mode === 'online' || $item->product?->meeting_link)
                            En ligne
                        @elseif ($item->product?->location)
                            {{ $item->product->location }}
                        @endif
                        @if ($item->product?->duration_minutes) · {{ $item->product->duration_minutes }} min @endif
                    </p>
                </div>
                @if ($item->product?->meeting_link)
                    <a href="{{ $item->product->meeting_link }}" target="_blank" rel="noopener" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3.5 text-sm font-semibold text-white">Rejoindre</a>
                @endif
            </div>
        </div>
    @empty
        <x-ui.empty-state title="Aucun rendez-vous à venir" text="Réservez une prestation depuis la boutique.">
            <x-slot:cta>
                <x-ui.button :href="route('catalog.index', ['type' => 'service'])" variant="primary">Voir les prestations</x-ui.button>
            </x-slot:cta>
        </x-ui.empty-state>
    @endforelse
</section>

@if ($past->isNotEmpty())
    <section class="mt-10">
        <h2 class="mb-3 font-display text-sm font-semibold uppercase tracking-wide text-ink-2">Passés</h2>
        @foreach ($past as $item)
            <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-black/[0.06] bg-surface px-4 py-3 text-sm">
                <span class="text-ink-2">{{ $item->product_name }}</span>
                <span class="text-ink-3">{{ $item->reservation_datetime->translatedFormat('d/m/Y H:i') }}</span>
            </div>
        @endforeach
    </section>
@endif
@endsection
