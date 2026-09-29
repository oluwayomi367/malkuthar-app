@extends('layouts.storefront')

@section('title', 'Mon panier — Place de marché MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
    {!!-- Header & Steps --!}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line/80 pb-6">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-accent">Place de marché</span>
            <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Mon panier</h1>
        </div>
        @if (!$lines->isEmpty())
            <div class="flex items-center gap-2 text-xs font-medium text-ink-3">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent text-white font-bold">1</span>
                <span class="font-semibold text-ink">Panier</span>
                <span class="text-line-strong">→</span>
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-line bg-surface text-ink-3">2</span>
                <span>Commande</span>
            </div>
        @endif
    </div>

    @if ($lines->isEmpty())
        <div class="mt-12 rounded-3xl border border-line/80 bg-surface p-12 text-center shadow-[0_4px_24px_-10px_rgba(46,44,40,0.06)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ground-alt text-ink-3">
                <svg class="h-8 w-8 stroke-[1.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <h2 class="mt-4 font-display text-lg font-bold text-ink">Votre panier est actuellement vide</h2>
            <p class="mt-2 text-sm text-ink-2 max-w-md mx-auto leading-relaxed">
                Parcourez les offres de nos créateurs, artisans et praticiens et ajoutez vos coups de cœur.
            </p>
            <div class="mt-6">
                <x-ui.button variant="primary" :href="route('marketplace.shop')" class="h-11 px-6 text-xs font-semibold uppercase tracking-wider">
                    Découvrir les offres
                </x-ui.button>
            </div>
        </div>
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:items-start">
            {!!-- Left: Vendor Lines --!}
            <div class="space-y-6">
                @foreach ($byVendor as $vendorName => $vendorLines)
                    <div class="overflow-hidden rounded-2xl border border-line/90 bg-surface shadow-[0_2px_12px_-4px_rgba(46,44,40,0.04)]">
                        <div class="flex items-center justify-between border-b border-line/80 bg-ground-alt/40 px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-accent-soft/70 text-accent font-bold text-xs">
                                    {{ mb_substr($vendorName, 0, 1) }}
                                </span>
                                <span class="text-xs font-bold text-ink">{{ $vendorName }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-accent">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 7-7 9-4-2-7-4-7-9V7z" />
                                </svg>
                                Vendeur vérifié
                            </span>
                        </div>

                        <div class="divide-y divide-line/70 px-5">
                            @foreach ($vendorLines as $line)
                                <div class="flex flex-wrap items-center gap-4 py-4 sm:flex-nowrap">
                                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-line/80 bg-ground-alt">
                                        @if ($line['variant']?->image_path ?? $line['offer']->main_image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($line['variant']?->image_path ?? $line['offer']->main_image_path) }}"
                                                 alt="{{ $line['offer']->title }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-center justify-center text-ink-3/40">
                                                <svg class="h-6 w-6 stroke-[1.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('marketplace.shop.offer', $line['offer']->slug) }}"
                                           class="line-clamp-1 font-semibold text-sm text-ink transition-colors hover:text-accent">
                                            {{ $line['offer']->title }}
                                        </a>
                                        @if ($line['variant'])
                                            <span class="mt-0.5 inline-block rounded-md bg-ground-alt px-2 py-0.5 text-[11px] font-medium text-ink-2">
                                                {{ $line['variant']->optionsLabel() }}
                                            </span>
                                        @endif
                                        <p class="mt-1 text-xs text-ink-3">
                                            {{ $money($line['unit_price']) }} {{ $line['offer']->currency }} / unité
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <form method="POST" action="{{ route('marketplace.cart.update') }}" class="flex items-center">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                            <div class="relative">
                                                <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="0" max="50"
                                                       class="h-9 w-16 rounded-xl border border-line/90 bg-surface px-2.5 text-center font-mono text-xs font-semibold text-ink transition-all focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/15"
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>

                                        <div class="w-24 text-right">
                                            <span class="font-display text-sm font-bold text-ink">
                                                {{ $money($line['line_total']) }}
                                            </span>
                                            <span class="block text-[10px] text-ink-3 font-semibold">{{ $line['offer']->currency }}</span>
                                        </div>

                                        <form method="POST" action="{{ route('marketplace.cart.remove') }}">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                            <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-3 transition-colors hover:bg-rust/10 hover:text-rust" title="Retirer cet article">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {!!-- Right: Summary Card --!}
            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_8px_30px_-12px_rgba(46,44,40,0.08)]">
                    <h2 class="font-display text-base font-bold text-ink border-b border-line/70 pb-4">
                        Récapitulatif de la commande
                    </h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between text-ink-2">
                            <span>Nombre d'articles</span>
                            <span class="font-semibold text-ink">{{ $lines->sum('quantity') }}</span>
                        </div>
                        <div class="flex justify-between text-ink-2">
                            <span>Sous-total articles</span>
                            <span class="font-semibold text-ink">{{ $money($subtotal) }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-xs text-ink-3">
                            <span>Livraison</span>
                            <span>Calculée à l'étape suivante</span>
                        </div>
                    </dl>

                    <div class="mt-5 flex items-baseline justify-between border-t border-line/80 pt-4">
                        <span class="font-display text-base font-bold text-ink">Total estimé</span>
                        <div class="text-right">
                            <span class="font-display text-2xl font-bold tracking-tight text-ink">
                                {{ $money($subtotal) }}
                            </span>
                            <span class="text-xs font-semibold text-accent ml-1">FCFA</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-ui.button variant="primary" :href="route('marketplace.checkout')" full class="h-12 text-xs font-semibold uppercase tracking-wider">
                            <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                            Passer la commande
                        </x-ui.button>
                    </div>

                    <p class="mt-3 text-center text-[11px] text-ink-3">
                        Transactions sécurisées & arbitrage garanti par MALKUTHAR
                    </p>
                </div>

                <div class="rounded-2xl border border-line/80 bg-ground-alt/40 p-4 text-xs text-ink-2">
                    <div class="flex items-center gap-2 text-ink font-semibold">
                        <svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        Garantie MALKUTHAR Marketplace
                    </div>
                    <p class="mt-1.5 text-ink-3 leading-relaxed">
                        Chaque créateur est vérifié. En cas de problème ou retard de livraison, notre équipe intervient et protège vos intérêts.
                    </p>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection

