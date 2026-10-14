@extends('layouts.storefront')

@section('title', 'Finaliser la commande — Place de marché MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 sm:py-14">
    {{-- En-tête et étapes --}}
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
        <div>
            <p class="text-sm text-ink-2">Place de marché</p>
            <h1 class="mt-1 font-display text-[34px] font-semibold leading-tight text-ink sm:text-[40px]">Finaliser ma commande</h1>
        </div>
        <ol class="flex items-center gap-3 text-sm text-ink-3" aria-label="Étapes de la commande">
            <li>
                <a href="{{ route('marketplace.cart') }}" class="flex min-h-11 items-center gap-2 text-ink-2 hover:text-ink">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft text-accent-2">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    Panier
                </a>
            </li>
            <li aria-hidden="true" class="h-px w-6 bg-line-strong"></li>
            <li class="flex items-center gap-2 font-semibold text-ink" aria-current="step">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">2</span>
                Commande
            </li>
        </ol>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start">
        {{-- Formulaire --}}
        <form method="POST" action="{{ route('marketplace.checkout.store') }}" class="space-y-6">
            @csrf

            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <h2 class="flex items-center gap-3 font-sans text-lg font-semibold tracking-normal text-ink">
                    <span aria-hidden="true" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm">1</span>
                    Vos coordonnées
                </h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email"
                                :value="old('email', auth()->user()?->email)" placeholder="nom@exemple.com" />
                    <x-ui.field name="phone" label="Numéro de téléphone" :required="true" autocomplete="tel"
                                :value="old('phone', auth()->user()?->phone)" placeholder="Ex. : +237 6XX XX XX XX" />
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <h2 class="flex items-center gap-3 font-sans text-lg font-semibold tracking-normal text-ink">
                    <span aria-hidden="true" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm">2</span>
                    Adresse de livraison
                </h2>
                <p class="mt-1 pl-10 text-sm text-ink-2">Nécessaire pour les produits physiques.</p>
                <div class="mt-5 grid gap-5">
                    <x-ui.field name="address_line" label="Adresse ou repère précis" autocomplete="street-address"
                                :value="old('address_line')" placeholder="Quartier, rue, numéro de porte ou repère…" />
                    <x-ui.field name="city" label="Ville & Région" autocomplete="address-level2"
                                :value="old('city')" placeholder="Ex. : Douala, Yaoundé, Bafoussam…" />
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <h2 class="flex items-center gap-3 font-sans text-lg font-semibold tracking-normal text-ink">
                    <span aria-hidden="true" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ground-alt text-sm">3</span>
                    Mode de règlement
                </h2>

                <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border-2 border-accent bg-accent-soft/50 p-4">
                    <input type="radio" name="payment_method" value="cod" checked
                           class="mt-0.5 h-5 w-5 shrink-0 border-line-strong text-accent focus:ring-accent/30">
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold text-ink">Paiement à la livraison ou sur place</span>
                        <span class="mt-1 block text-sm leading-relaxed text-ink-2">
                            Vous réglez directement le livreur ou le praticien lors de la remise ou de la prestation, après avoir vérifié vos articles ou votre séance.
                        </span>
                    </span>
                </label>
            </section>

            <x-ui.button type="submit" variant="primary" :full="true">
                Confirmer et valider ma commande
            </x-ui.button>
        </form>

        {{-- Récapitulatif --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-display text-[26px] font-semibold text-ink">Votre commande</h2>
                    <a href="{{ route('marketplace.cart') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-ink underline decoration-accent underline-offset-4 hover:text-accent-2">Modifier</a>
                </div>

                <div class="mt-4 divide-y divide-line">
                    @foreach ($byVendor as $vendorName => $vendorLines)
                        <div class="py-4 first:pt-0">
                            <h3 class="text-sm font-semibold text-ink-2">{{ $vendorName }}</h3>
                            <ul class="mt-2 space-y-3">
                                @foreach ($vendorLines as $line)
                                    <li class="flex items-start justify-between gap-3 text-sm">
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-2 font-medium text-ink">{{ $line['offer']->title }}</p>
                                            @if ($line['variant'])
                                                <p class="text-ink-3">{{ $line['variant']->optionsLabel() }}</p>
                                            @endif
                                            <p class="text-ink-3">{{ $line['quantity'] }} × {{ $money($line['unit_price']) }} {{ $line['offer']->currency }}</p>
                                        </div>
                                        <span class="shrink-0 font-semibold text-ink">{{ $money($line['line_total']) }} FCFA</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-2 space-y-2 border-t border-line pt-4 text-[15px]">
                    <div class="flex justify-between gap-4 text-ink-2">
                        <dt>Sous-total</dt>
                        <dd class="font-semibold text-ink">{{ $money($subtotal) }} FCFA</dd>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-ink-3">
                        <dt>Livraison</dt>
                        <dd>À régler à la remise</dd>
                    </div>
                </dl>
                <div class="mt-4 flex items-baseline justify-between gap-4 border-t border-line pt-4">
                    <span class="text-[15px] font-semibold text-ink">Total à régler</span>
                    <span class="font-display text-[32px] font-semibold leading-none text-ink">
                        {{ $money($subtotal) }} <span class="font-sans text-sm font-semibold text-ink-2">FCFA</span>
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-line bg-ground-alt p-4 text-sm text-ink-2">
                <p class="font-semibold text-ink">Une commande, plusieurs vendeurs</p>
                <p class="mt-1 leading-relaxed">
                    Chaque vendeur reçoit sa propre sous-commande et la suit séparément. Vos coordonnées ne servent qu'à la livraison et au suivi.
                </p>
            </div>
        </aside>
    </div>
</div>
@endsection
