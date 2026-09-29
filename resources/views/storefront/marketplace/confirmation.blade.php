@extends('layouts.storefront')

@section('title', 'Commande confirmée ' . $order->order_number . ' — MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-16">
    {!!-- Celebratory Header --!}
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-green/15 text-brand-green ring-8 ring-brand-green/5">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-accent">Merci pour votre confiance</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Commande confirmée avec succès</h1>
        <p class="mt-2 text-sm text-ink-2 max-w-md mx-auto leading-relaxed">
            Votre commande a bien été transmise à nos créateurs et partenaires. Un récapitulatif a été généré sous la référence :
        </p>
        <div class="mt-3 inline-flex items-center gap-2 rounded-xl border border-line/90 bg-surface px-4 py-2 shadow-2xs">
            <span class="text-xs text-ink-3">Référence :</span>
            <span class="font-mono text-sm font-bold text-ink">{{ $order->order_number }}</span>
        </div>
    </div>

    {!!-- Status & Next Steps --!}
    <div class="mt-8 rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_4px_24px_-10px_rgba(46,44,40,0.06)]">
        <h2 class="font-display text-sm font-bold text-ink border-b border-line/70 pb-3">Prochaines étapes</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3 text-xs">
            <div class="space-y-1">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent font-bold">1</span>
                <p class="font-semibold text-ink">Transmission</p>
                <p class="text-ink-3">Chaque créateur est notifié pour préparer vos articles.</p>
            </div>
            <div class="space-y-1">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent font-bold">2</span>
                <p class="font-semibold text-ink">Expédition & Suivi</p>
                <p class="text-ink-3">Prise en charge de la livraison physique ou de l'accès direct.</p>
            </div>
            <div class="space-y-1">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent font-bold">3</span>
                <p class="font-semibold text-ink">Règlement</p>
                <p class="text-ink-3">
                    @if ($order->payment_status !== 'paid')
                        Paiement direct à la livraison ou lors de la prestation.
                    @else
                        Paiement validé avec succès.
                    @endif
                </p>
            </div>
        </div>
    </div>

    {!!-- Suborders by Vendor --!}
    <div class="mt-6 space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-ink-3 px-1">Détail par vendeur</h2>
        @foreach ($order->suborders as $sub)
            <div class="rounded-2xl border border-line/90 bg-surface p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-line/70 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-accent-soft text-accent font-bold text-xs">
                            {{ mb_substr($sub->vendor->display_name, 0, 1) }}
                        </span>
                        <span class="text-xs font-bold text-ink">{{ $sub->vendor->display_name }}</span>
                    </div>
                    <span class="rounded-md bg-ground-alt px-2 py-0.5 font-mono text-[10px] text-ink-3">
                        Sous-commande #{{ $sub->id }}
                    </span>
                </div>

                <ul class="mt-3 space-y-2 text-xs">
                    @foreach ($sub->items as $item)
                        <li class="flex items-center justify-between text-ink-2">
                            <span class="line-clamp-1 flex-1 pr-4">
                                <span class="font-semibold text-ink">{{ $item->quantity }} ×</span> {{ $item->title }}
                            </span>
                            <span class="font-semibold text-ink shrink-0">{{ $money($item->line_total) }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    {!!-- Total Summary --!}
    <div class="mt-6 flex items-baseline justify-between rounded-2xl border border-line/90 bg-ground-alt/60 px-6 py-4">
        <div>
            <span class="font-display text-sm font-bold text-ink">Montant total de la commande</span>
            <p class="text-[11px] text-ink-3">Taxes et gestion incluses</p>
        </div>
        <div class="text-right">
            <span class="font-display text-2xl font-bold tracking-tight text-ink">
                {{ $money($order->grand_total) }}
            </span>
            <span class="text-xs font-semibold text-accent ml-1">{{ $order->currency }}</span>
        </div>
    </div>

    {!!-- Actions --!}
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <x-ui.button variant="primary" :href="route('marketplace.shop')" class="h-11 px-6 text-xs font-semibold uppercase tracking-wider w-full sm:w-auto">
            Continuer mes achats
        </x-ui.button>
        @auth
            <x-ui.button variant="secondary" :href="route('account.orders.index')" class="h-11 px-6 text-xs font-semibold uppercase tracking-wider w-full sm:w-auto">
                Consulter mes commandes
            </x-ui.button>
        @endauth
    </div>
</div>
@endsection

