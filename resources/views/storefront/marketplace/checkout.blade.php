@extends('layouts.storefront')

@section('title', 'Finaliser la commande — Place de marché MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
    {!!-- Header & Steps --!}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line/80 pb-6">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-accent">Place de marché</span>
            <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Finaliser ma commande</h1>
        </div>
        <div class="flex items-center gap-2 text-xs font-medium text-ink-3">
            <a href="{{ route('marketplace.cart') }}" class="inline-flex items-center gap-1.5 text-accent hover:underline">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent font-bold">✓</span>
                <span>Panier</span>
            </a>
            <span class="text-line-strong">→</span>
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent text-white font-bold">2</span>
            <span class="font-semibold text-ink">Commande</span>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-start">
        {!!-- Left: Form --!}
        <form method="POST" action="{{ route('marketplace.checkout.store') }}" class="space-y-6">
            @csrf

            {!!-- Step 1: Contact --!}
            <div class="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.05)]">
                <div class="flex items-center gap-2.5 border-b border-line/70 pb-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft text-accent font-bold text-xs">1</span>
                    <h2 class="font-display text-base font-bold text-ink">Vos coordonnées</h2>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="email" label="Adresse email" type="email" :required="true"
                                :value="old('email', auth()->user()?->email)" placeholder="nom@exemple.com" />
                    <x-ui.field name="phone" label="Numéro de téléphone" :required="true"
                                :value="old('phone', auth()->user()?->phone)" placeholder="Ex. : +237 6XX XX XX XX" />
                </div>
            </div>

            {!!-- Step 2: Shipping / Delivery Address --!}
            <div class="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.05)]">
                <div class="flex items-center gap-2.5 border-b border-line/70 pb-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft text-accent font-bold text-xs">2</span>
                    <div>
                        <h2 class="font-display text-base font-bold text-ink">Adresse de livraison</h2>
                        <p class="text-xs text-ink-3">Nécessaire pour les commandes de produits physiques</p>
                    </div>
                </div>
                <div class="mt-5 space-y-4">
                    <x-ui.field name="address_line" label="Adresse ou repère précis"
                                :value="old('address_line')" placeholder="Quartier, rue, numéro de porte ou repère…" />
                    <x-ui.field name="city" label="Ville & Région"
                                :value="old('city')" placeholder="Ex. : Douala, Yaoundé, Bafoussam…" />
                </div>
            </div>

            {!!-- Step 3: Payment Method --!}
            <div class="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.05)]">
                <div class="flex items-center gap-2.5 border-b border-line/70 pb-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft text-accent font-bold text-xs">3</span>
                    <h2 class="font-display text-base font-bold text-ink">Mode de règlement</h2>
                </div>

                <div class="mt-5">
                    <label class="flex cursor-pointer items-start gap-3.5 rounded-2xl border-2 border-accent bg-accent-soft/30 p-4 transition-all">
                        <input type="radio" name="payment_method" value="cod" checked
                               class="mt-1 h-4 w-4 border-line text-accent focus:ring-accent">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-ink">Paiement à la livraison ou sur place</span>
                                <span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-white uppercase">Recommandé</span>
                            </div>
                            <p class="mt-1 text-xs text-ink-2 leading-relaxed">
                                Réglez votre commande directement auprès du livreur ou du praticien lors de la remise ou de la prestation.
                            </p>
                        </div>
                    </label>

                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-ground-alt/60 p-3 text-xs text-ink-2">
                        <svg class="h-4 w-4 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <span>Règlement direct garanti : vérifiez vos articles ou votre séance avant le paiement auprès du vendeur ou livreur.</span>
                    </div>
                </div>
            </div>

            <div>
                <x-ui.button type="submit" variant="primary" full class="h-13 text-xs font-semibold uppercase tracking-wider">
                    <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                    Confirmer et valider ma commande
                </x-ui.button>
            </div>
        </form>

        {!!-- Right: Order Recap --!}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_8px_30px_-12px_rgba(46,44,40,0.08)]">
                <div class="flex items-center justify-between border-b border-line/70 pb-4">
                    <h2 class="font-display text-base font-bold text-ink">Articles commandés</h2>
                    <a href="{{ route('marketplace.cart') }}" class="text-xs font-semibold text-accent hover:underline">Modifier</a>
                </div>

                <div class="mt-4 space-y-4 divide-y divide-line/60">
                    @foreach ($byVendor as $vendorName => $vendorLines)
                        <div class="pt-3 first:pt-0">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-accent">{{ $vendorName }}</p>
                            <div class="mt-2 space-y-2.5">
                                @foreach ($vendorLines as $line)
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <span class="font-medium text-ink line-clamp-1">{{ $line['offer']->title }}</span>
                                            <span class="text-ink-3">{{ $line['quantity'] }} × {{ $money($line['unit_price']) }} {{ $line['offer']->currency }}</span>
                                            @if ($line['variant'])
                                                <span class="block text-[10px] text-ink-3">({{ $line['variant']->optionsLabel() }})</span>
                                            @endif
                                        </div>
                                        <span class="font-display font-bold text-ink shrink-0">{{ $money($line['line_total']) }} FCFA</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 border-t border-line/80 pt-4 space-y-2">
                    <div class="flex justify-between text-xs text-ink-2">
                        <span>Sous-total</span>
                        <span class="font-medium text-ink">{{ $money($subtotal) }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-xs text-ink-3">
                        <span>Livraison</span>
                        <span>À régler à la remise</span>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-line/60 pt-3">
                        <span class="font-display text-base font-bold text-ink">Total à régler</span>
                        <div class="text-right">
                            <span class="font-display text-2xl font-bold tracking-tight text-ink">
                                {{ $money($subtotal) }}
                            </span>
                            <span class="text-xs font-semibold text-accent ml-1">FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-line/80 bg-ground-alt/40 p-4 text-xs text-ink-2">
                <div class="flex items-center gap-2 text-ink font-semibold">
                    <svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0110 0v4" />
                    </svg>
                    Sécurité & Engagement
                </div>
                <p class="mt-1.5 text-ink-3 leading-relaxed">
                    Vos données sont confidentielles. Chaque commande génère des sous-commandes directes auprès de chaque créateur pour un suivi rigoureux.
                </p>
            </div>
        </aside>
    </div>
</div>
@endsection

