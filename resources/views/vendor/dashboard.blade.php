@extends('layouts.app')

@section('title', 'Espace vendeur')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $done = collect($checklist)->where('done', true)->count();
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'];

    $toProcess = $recentOrders->filter(fn ($s) => in_array($s->fulfilment_status, ['unfulfilled', 'processing'], true)
        && $s->order?->payment_status === 'paid');
    $arrow = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
@endphp

<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">{{ $profile->display_name }}</h1>
        <p class="mt-2 text-[15px] text-ink-2">
            {{ $profile->region }}@if ($profile->city) · {{ $profile->city }}@endif
            · active depuis {{ optional($profile->approved_at ?? $profile->created_at)->translatedFormat('F Y') }}
        </p>
    </div>
    <a href="{{ route('vendor.offers.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nouvelle offre
    </a>
</header>

{{-- Chiffres --}}
<div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-tile label="Revenu disponible" :value="$money($balance->available_amount)" :currency="$profile->payout_currency" emphasis>
        <x-slot:foot>En attente : {{ $money($balance->pending_amount) }} {{ $profile->payout_currency }}</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Offres en ligne" :value="$stats['offers_online']">
        <x-slot:foot>{{ $stats['offers_draft'] }} brouillon{{ $stats['offers_draft'] > 1 ? 's' : '' }}</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="En validation" :value="$stats['offers_pending']">
        <x-slot:foot>
            @if ($stats['offers_changes'] > 0)
                <span class="font-semibold text-amber">{{ $stats['offers_changes'] }} à corriger</span>
            @else
                Relues par l'équipe avant publication
            @endif
        </x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Commandes à traiter" :value="$stats['to_ship']" />
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:items-start">
    <div class="space-y-6">
        {{-- À traiter maintenant --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="todo-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="todo-title" class="font-sans text-lg font-semibold text-ink">À traiter maintenant</h2>
                <a href="{{ route('vendor.orders.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">Toutes les commandes {!! $arrow !!}</a>
            </div>

            @if ($toProcess->isEmpty())
                <p class="mt-2 text-sm leading-relaxed text-ink-2">Aucune commande en attente de préparation.</p>
            @else
                <ul class="mt-2 divide-y divide-line border-t border-line">
                    @foreach ($toProcess as $sub)
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3.5">
                            <div class="min-w-0">
                                <p class="font-mono text-sm text-ink">{{ $sub->suborder_number }}</p>
                                <p class="mt-0.5 text-[13px] text-ink-3">Payée le {{ optional($sub->order->paid_at)->format('d/m/Y') }} · Net {{ $money($sub->vendor_net) }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-ui.status-pill :status="$sub->fulfilment_status" />
                                <a href="{{ route('vendor.orders.show', $sub) }}" class="inline-flex min-h-11 items-center rounded-full border border-line-strong px-4 text-sm font-semibold text-ink transition-colors hover:border-ink">
                                    Traiter<span class="sr-only"> la commande {{ $sub->suborder_number }}</span>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($stats['to_ship'] > $toProcess->count())
                    <p class="mt-3 text-[13px] text-ink-2">{{ $stats['to_ship'] - $toProcess->count() }} autre(s) commande(s) à traiter dans la liste complète.</p>
                @endif
            @endif

            @php $others = $recentOrders->diff($toProcess); @endphp
            @if ($others->isNotEmpty())
                <h3 class="mt-6 text-sm font-semibold text-ink-2">Dernières commandes</h3>
                <ul class="mt-1 divide-y divide-line">
                    @foreach ($others as $sub)
                        <li>
                            <a href="{{ route('vendor.orders.show', $sub) }}" class="flex min-h-11 flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3 text-sm">
                                <span>
                                    <span class="font-mono text-ink">{{ $sub->suborder_number }}</span>
                                    <span class="ml-2 text-ink-3">{{ optional($sub->order->paid_at)->format('d/m/Y') ?? 'paiement en attente' }}</span>
                                </span>
                                <span class="flex items-center gap-3">
                                    <span class="font-semibold text-ink">{{ $money($sub->vendor_net) }}</span>
                                    <x-ui.status-pill :status="$sub->fulfilment_status" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Offres --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="offers-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="offers-title" class="font-sans text-lg font-semibold text-ink">Mes offres</h2>
                <a href="{{ route('vendor.offers.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">Toutes mes offres {!! $arrow !!}</a>
            </div>
            @if ($recentOffers->isEmpty())
                <p class="mt-2 text-sm leading-relaxed text-ink-2">
                    Vous n'avez pas encore d'offre.
                    <a href="{{ route('vendor.offers.create') }}" class="font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">Créez la première</a>.
                </p>
            @else
                <ul class="mt-2 divide-y divide-line border-t border-line">
                    @foreach ($recentOffers as $offer)
                        <li>
                            <a href="{{ route('vendor.offers.edit', $offer) }}" class="flex items-center gap-3 py-3 text-sm">
                                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-ground-alt">
                                    @if ($offer->main_image_path)
                                        <img src="{{ Storage::url($offer->main_image_path) }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="line-clamp-1 font-medium text-ink">{{ $offer->title }}</span>
                                    <span class="text-[13px] text-ink-3">{{ $typeLabel[$offer->type] ?? ucfirst($offer->type) }} · {{ $money($offer->sale_price ?? $offer->price) }} {{ $offer->currency }}</span>
                                </span>
                                <x-ui.status-pill :status="$offer->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="space-y-6">
        {{-- Bien démarrer --}}
        @if ($done < count($checklist))
            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="start-title">
                <div class="flex items-baseline justify-between gap-2">
                    <h2 id="start-title" class="font-sans text-lg font-semibold text-ink">Bien démarrer</h2>
                    <span class="text-sm text-ink-2">{{ $done }} sur {{ count($checklist) }}</span>
                </div>
                <ul class="mt-3 divide-y divide-line">
                    @foreach ($checklist as $item)
                        <li class="flex min-h-11 items-center gap-3 py-2 text-sm">
                            @if ($item['done'])
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-space-vendor">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="text-ink-2">{{ $item['label'] }}<span class="sr-only"> (fait)</span></span>
                            @else
                                <span class="h-6 w-6 shrink-0 rounded-full border border-line-strong" aria-hidden="true"></span>
                                <a href="{{ route($item['route']) }}" class="font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">{{ $item['label'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Boutique --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="shop-title">
            <h2 id="shop-title" class="font-sans text-lg font-semibold text-ink">Ma boutique</h2>
            <p class="mt-1 text-sm text-ink-2">
                Page publique :
                <a href="{{ route('marketplace.shop.vendor', $profile->slug) }}" class="break-all font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">/{{ $profile->slug }}</a>
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button variant="secondary" :href="route('vendor.profile.edit')">Modifier la boutique</x-ui.button>
                <x-ui.button variant="ghost" :href="route('vendor.settlement.index')">Mes revenus</x-ui.button>
            </div>
        </section>
    </div>
</div>
@endsection
