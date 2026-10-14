@extends('layouts.storefront')

@section('title', 'Commande confirmée ' . $order->order_number . ' — MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-16">
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sage-soft text-sage">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <h1 class="mt-5 font-display text-[34px] font-semibold leading-tight text-ink sm:text-[40px]">Commande confirmée</h1>
        <p class="mx-auto mt-3 max-w-md text-[15px] leading-relaxed text-ink-2">
            Merci. Votre commande a été transmise aux vendeurs concernés.
        </p>
        <p class="mt-4 inline-flex flex-wrap items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 text-sm">
            <span class="text-ink-2">Référence</span>
            <span class="font-mono font-semibold text-ink">{{ $order->order_number }}</span>
        </p>
    </div>

    {{-- Prochaines étapes --}}
    <section class="mt-10 rounded-2xl border border-line bg-surface p-5 sm:p-6">
        <h2 class="font-display text-[26px] font-semibold text-ink">Prochaines étapes</h2>
        <ol class="mt-4 grid gap-5 sm:grid-cols-3">
            <li>
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm font-semibold text-ink">1</span>
                <p class="mt-2 text-[15px] font-semibold text-ink">Transmission</p>
                <p class="mt-1 text-sm leading-relaxed text-ink-2">Chaque vendeur est notifié et prépare vos articles.</p>
            </li>
            <li>
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm font-semibold text-ink">2</span>
                <p class="mt-2 text-[15px] font-semibold text-ink">Livraison ou accès</p>
                <p class="mt-1 text-sm leading-relaxed text-ink-2">Expédition des produits physiques, ou accès direct pour le reste.</p>
            </li>
            <li>
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm font-semibold text-ink">3</span>
                <p class="mt-2 text-[15px] font-semibold text-ink">Règlement</p>
                <p class="mt-1 text-sm leading-relaxed text-ink-2">
                    @if ($order->payment_status !== 'paid')
                        Paiement direct à la livraison ou lors de la prestation.
                    @else
                        Paiement reçu.
                    @endif
                </p>
            </li>
        </ol>
    </section>

    {{-- Détail par vendeur --}}
    <section class="mt-8">
        <h2 class="font-display text-[26px] font-semibold text-ink">Détail par vendeur</h2>
        <div class="mt-4 space-y-4">
            @foreach ($order->suborders as $sub)
                <div class="rounded-2xl border border-line bg-surface p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line pb-3">
                        <h3 class="text-base font-semibold text-ink">{{ $sub->vendor->display_name }}</h3>
                        <span class="text-xs text-ink-3">Sous-commande n° {{ $sub->id }}</span>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($sub->items as $item)
                            <li class="flex items-start justify-between gap-4 text-ink-2">
                                <span class="min-w-0 flex-1">
                                    <span class="font-semibold text-ink">{{ $item->quantity }} ×</span> {{ $item->title }}
                                </span>
                                <span class="shrink-0 font-semibold text-ink">{{ $money($item->line_total) }} FCFA</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Total --}}
    <div class="mt-6 flex flex-wrap items-baseline justify-between gap-4 rounded-2xl border border-line bg-ground-alt px-5 py-5 sm:px-6">
        <div>
            <p class="text-[15px] font-semibold text-ink">Montant total</p>
            <p class="text-sm text-ink-2">Taxes et frais de gestion inclus</p>
        </div>
        <p class="font-display text-[34px] font-semibold leading-none text-ink">
            {{ $money($order->grand_total) }} <span class="font-sans text-sm font-semibold text-ink-2">{{ $order->currency }}</span>
        </p>
    </div>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <x-ui.button variant="primary" :href="route('marketplace.shop')" class="w-full sm:w-auto">
            Continuer mes achats
        </x-ui.button>
        @auth
            <x-ui.button variant="secondary" :href="route('account.orders')" class="w-full sm:w-auto">
                Consulter mes commandes
            </x-ui.button>
        @endauth
    </div>
</div>
@endsection
