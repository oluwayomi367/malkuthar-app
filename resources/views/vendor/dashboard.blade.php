@extends('layouts.app')

@section('title', 'Espace vendeur')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $done = collect($checklist)->where('done', true)->count();
@endphp

<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{{ $profile->display_name }}</h1>
        <p class="mt-1 text-sm text-ink-2">
            {{ $profile->region }}@if ($profile->city) · {{ $profile->city }}@endif —
            actif depuis {{ optional($profile->approved_at ?? $profile->created_at)->translatedFormat('F Y') }}
        </p>
    </div>
    <x-ui.button variant="primary" :href="route('vendor.offers.create')">+ Nouvelle offre</x-ui.button>
</div>

{{-- ===== Chiffres ===== --}}
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <x-ui.stat-tile label="Revenu disponible" :value="$money($balance->available_amount)" :currency="$profile->payout_currency" emphasis>
        <x-slot:foot>En attente : {{ $money($balance->pending_amount) }}</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Offres en ligne" :value="$stats['offers_online']">
        <x-slot:foot>{{ $stats['offers_draft'] }} brouillon(s)</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="En validation" :value="$stats['offers_pending']">
        <x-slot:foot>
            @if ($stats['offers_changes'] > 0)
                <span class="text-amber">{{ $stats['offers_changes'] }} à corriger</span>
            @else
                &nbsp;
            @endif
        </x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Commandes à traiter" :value="$stats['to_ship']" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
    {{-- ===== Colonne principale ===== --}}
    <div class="space-y-6">
        {{-- Commandes récentes --}}
        <section class="rounded-2xl border border-line bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Commandes récentes</h2>
                <a href="{{ route('vendor.orders.index') }}" class="text-xs font-semibold text-accent hover:underline">Tout voir</a>
            </div>
            @forelse ($recentOrders as $sub)
                <a href="{{ route('vendor.orders.show', $sub) }}" class="mt-3 flex items-center justify-between border-t border-line pt-3 text-sm first:mt-3">
                    <span>
                        <span class="font-mono text-ink">{{ $sub->suborder_number }}</span>
                        <span class="block text-xs text-ink-3">{{ optional($sub->order->paid_at)->format('d/m/Y') ?? 'paiement en attente' }}</span>
                    </span>
                    <span class="flex items-center gap-2 text-right">
                        <span class="font-semibold text-ink">{{ $money($sub->vendor_net) }}</span>
                        <x-ui.status-pill :status="$sub->fulfilment_status" />
                    </span>
                </a>
            @empty
                <p class="mt-3 text-sm text-ink-3">Aucune commande pour l'instant.</p>
            @endforelse
        </section>

        {{-- Offres récentes --}}
        <section class="rounded-2xl border border-line bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Mes offres</h2>
                <a href="{{ route('vendor.offers.index') }}" class="text-xs font-semibold text-accent hover:underline">Tout voir</a>
            </div>
            @forelse ($recentOffers as $offer)
                <a href="{{ route('vendor.offers.edit', $offer) }}" class="mt-3 flex items-center gap-3 border-t border-line pt-3 text-sm first:mt-3">
                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-ground-alt">
                        @if ($offer->main_image_path)
                            <img src="{{ Storage::url($offer->main_image_path) }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-1 font-medium text-ink">{{ $offer->title }}</span>
                        <span class="text-xs text-ink-3">{{ ucfirst($offer->type) }} · {{ $money($offer->sale_price ?? $offer->price) }} {{ $offer->currency }}</span>
                    </span>
                    <x-ui.status-pill :status="$offer->status" />
                </a>
            @empty
                <p class="mt-3 text-sm text-ink-3">
                    Vous n'avez pas encore d'offre.
                    <a href="{{ route('vendor.offers.create') }}" class="font-semibold text-accent hover:underline">Créez la première</a>.
                </p>
            @endforelse
        </section>
    </div>

    {{-- ===== Colonne latérale ===== --}}
    <div class="space-y-6">
        {{-- Checklist de démarrage --}}
        <section class="rounded-2xl border border-line bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Bien démarrer</h2>
                <span class="text-xs text-ink-3">{{ $done }}/{{ count($checklist) }}</span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ground-alt">
                <div class="h-full bg-accent" style="width: {{ count($checklist) ? round($done / count($checklist) * 100) : 0 }}%"></div>
            </div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($checklist as $item)
                    <li class="flex items-center gap-2.5">
                        <span @class([
                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[11px]',
                            'border-accent bg-accent text-white' => $item['done'],
                            'border-line-strong text-transparent' => ! $item['done'],
                        ])>✓</span>
                        @if ($item['done'])
                            <span class="text-ink-3 line-through">{{ $item['label'] }}</span>
                        @else
                            <a href="{{ route($item['route']) }}" class="text-ink hover:text-accent">{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Raccourcis --}}
        <section class="rounded-2xl border border-line bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Ma boutique</h2>
            <p class="mt-1 text-sm text-ink-2">Vue publique : <a href="{{ route('marketplace.shop.vendor', $profile->slug) }}" class="text-accent hover:underline">/{{ $profile->slug }}</a></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.button variant="secondary" :href="route('vendor.profile.edit')">Modifier la boutique</x-ui.button>
                <x-ui.button variant="ghost" :href="route('vendor.settlement.index')">Mes revenus</x-ui.button>
            </div>
        </section>
    </div>
</div>
@endsection
