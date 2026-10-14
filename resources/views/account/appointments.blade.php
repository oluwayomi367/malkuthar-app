@extends('layouts.app')

@section('title', 'Mes rendez-vous')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes rendez-vous</h1>
    <p class="mt-2 text-[15px] text-ink-2">Vos prestations réservées, à venir et passées.</p>
</header>

<section class="mt-8" aria-labelledby="upcoming-title">
    <h2 id="upcoming-title" class="font-display text-[28px] font-semibold text-ink">À venir</h2>
    @if ($upcoming->isEmpty())
        <x-ui.empty-state class="mt-4" icon="calendar" title="Aucun rendez-vous à venir" text="Réservez une prestation depuis la boutique.">
            <x-slot:cta>
                <x-ui.button :href="route('catalog.index', ['type' => 'service'])" variant="primary">Voir les prestations</x-ui.button>
            </x-slot:cta>
        </x-ui.empty-state>
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($upcoming as $item)
                <li class="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-4">
                        <div class="flex w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-ground-alt py-2 text-ink">
                            <span class="text-xs font-semibold uppercase text-ink-2">{{ $item->reservation_datetime->translatedFormat('M') }}</span>
                            <span class="font-display text-[26px] font-semibold leading-none">{{ $item->reservation_datetime->format('d') }}</span>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-ink">{{ $item->product_name }}</p>
                            <p class="mt-1 text-sm text-ink-2">{{ ucfirst($item->reservation_datetime->translatedFormat('l d F Y · H:i')) }}</p>
                            <p class="mt-0.5 text-[13px] text-ink-3">
                                @if ($item->product?->delivery_mode === 'online' || $item->product?->meeting_link)
                                    En ligne
                                @elseif ($item->product?->location)
                                    {{ $item->product->location }}
                                @endif
                                @if ($item->product?->duration_minutes) · {{ $item->product->duration_minutes }} min @endif
                            </p>
                        </div>
                    </div>
                    @if ($item->product?->meeting_link)
                        <x-ui.button :href="$item->product->meeting_link" target="_blank" rel="noopener" variant="primary" class="shrink-0 self-start sm:self-center">Rejoindre</x-ui.button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>

@if ($past->isNotEmpty())
    <section class="mt-12" aria-labelledby="past-title">
        <h2 id="past-title" class="font-display text-[28px] font-semibold text-ink">Passés</h2>
        <ul class="mt-4 divide-y divide-line rounded-2xl border border-line bg-surface">
            @foreach ($past as $item)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3.5 text-sm">
                    <span class="text-ink">{{ $item->product_name }}</span>
                    <span class="text-ink-2">{{ $item->reservation_datetime->translatedFormat('d/m/Y H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
