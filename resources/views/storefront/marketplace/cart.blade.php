@extends('layouts.storefront')

@section('title', 'Mon panier — Place de marché MALKUTHAR')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 sm:py-14">
    {{-- En-tête et étapes --}}
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
        <div>
            <p class="text-sm text-ink-2">Place de marché</p>
            <h1 class="mt-1 font-display text-[34px] font-semibold leading-tight text-ink sm:text-[40px]">Mon panier</h1>
        </div>
        @if (!$lines->isEmpty())
            <ol class="flex items-center gap-3 text-sm text-ink-3" aria-label="Étapes de la commande">
                <li class="flex items-center gap-2 font-semibold text-ink" aria-current="step">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">1</span>
                    Panier
                </li>
                <li aria-hidden="true" class="h-px w-6 bg-line-strong"></li>
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-line-strong text-xs font-semibold">2</span>
                    Commande
                </li>
            </ol>
        @endif
    </div>

    @if ($lines->isEmpty())
        <div class="mx-auto mt-12 max-w-xl rounded-2xl border border-line bg-surface px-6 py-12 text-center">
            <svg class="mx-auto h-9 w-9 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-linejoin="round" />
            </svg>
            <h2 class="mt-4 font-display text-[26px] font-semibold text-ink">Votre panier est vide</h2>
            <p class="mx-auto mt-2 max-w-md text-[15px] leading-relaxed text-ink-2">
                Parcourez les offres des créateurs, artisans et praticiens de la place de marché.
            </p>
            <div class="mt-6">
                <x-ui.button variant="primary" :href="route('marketplace.shop')">Découvrir les offres</x-ui.button>
            </div>
        </div>
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
            {{-- Lignes par vendeur --}}
            <div class="space-y-6">
                @foreach ($byVendor as $vendorName => $vendorLines)
                    <section class="rounded-2xl border border-line bg-surface" aria-label="Articles de {{ $vendorName }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 sm:px-5">
                            <h2 class="font-sans text-base font-semibold tracking-normal text-ink">{{ $vendorName }}</h2>
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-sage">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 7-7 9-4-2-7-4-7-9V7z" stroke-linejoin="round" />
                                </svg>
                                Vendeur vérifié
                            </span>
                        </div>

                        <ul class="divide-y divide-line px-4 sm:px-5">
                            @foreach ($vendorLines as $line)
                                <li class="flex gap-4 py-4">
                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-ground-alt">
                                        @if ($line['variant']?->image_path ?? $line['offer']->main_image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($line['variant']?->image_path ?? $line['offer']->main_image_path) }}"
                                                 alt="{{ $line['offer']->title }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-center justify-center text-ink-3">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                                    <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('marketplace.shop.offer', $line['offer']->slug) }}"
                                               class="line-clamp-2 text-[15px] font-semibold text-ink hover:text-accent-2">
                                                {{ $line['offer']->title }}
                                            </a>
                                            @if ($line['variant'])
                                                <p class="mt-0.5 text-sm text-ink-2">{{ $line['variant']->optionsLabel() }}</p>
                                            @endif
                                            <p class="mt-1 text-sm text-ink-3">
                                                {{ $money($line['unit_price']) }} {{ $line['offer']->currency }} / unité
                                            </p>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                                            <form method="POST" action="{{ route('marketplace.cart.update') }}" class="flex items-center gap-2">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                                <label for="qty-{{ $line['key'] }}" class="sr-only">Quantité pour {{ $line['offer']->title }}</label>
                                                <input id="qty-{{ $line['key'] }}" type="number" name="quantity" value="{{ $line['quantity'] }}" min="0" max="50"
                                                       class="h-11 w-20 rounded-xl border border-line-strong bg-surface px-2 text-center text-[15px] font-semibold text-ink focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15"
                                                       onchange="this.form.submit()">
                                            </form>

                                            <p class="w-28 text-right text-[15px] font-semibold text-ink">
                                                {{ $money($line['line_total']) }} <span class="text-sm font-medium text-ink-2">{{ $line['offer']->currency }}</span>
                                            </p>

                                            <form method="POST" action="{{ route('marketplace.cart.remove') }}">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                                <button type="submit" class="flex h-11 w-11 items-center justify-center rounded-full text-ink-3 transition-colors hover:bg-ground-alt hover:text-rust" aria-label="Retirer {{ $line['offer']->title }} du panier">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            {{-- Récapitulatif --}}
            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                    <h2 class="font-display text-[26px] font-semibold text-ink">Récapitulatif</h2>

                    <dl class="mt-4 space-y-3 text-[15px]">
                        <div class="flex justify-between gap-4 text-ink-2">
                            <dt>Nombre d'articles</dt>
                            <dd class="font-semibold text-ink">{{ $lines->sum('quantity') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 text-ink-2">
                            <dt>Sous-total</dt>
                            <dd class="font-semibold text-ink">{{ $money($subtotal) }} FCFA</dd>
                        </div>
                        <div class="flex justify-between gap-4 text-sm text-ink-3">
                            <dt>Livraison</dt>
                            <dd>Calculée à l'étape suivante</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex items-baseline justify-between gap-4 border-t border-line pt-4">
                        <span class="text-[15px] font-semibold text-ink">Total estimé</span>
                        <span class="font-display text-[32px] font-semibold leading-none text-ink">
                            {{ $money($subtotal) }} <span class="font-sans text-sm font-semibold text-ink-2">FCFA</span>
                        </span>
                    </div>

                    <div class="mt-6">
                        <x-ui.button variant="primary" :href="route('marketplace.checkout')" :full="true">
                            Passer la commande
                        </x-ui.button>
                    </div>
                </div>

                <div class="rounded-2xl border border-line bg-ground-alt p-4 text-sm text-ink-2">
                    <p class="font-semibold text-ink">Vendeurs vérifiés</p>
                    <p class="mt-1 leading-relaxed">
                        Chaque vendeur est vérifié par MALKUTHAR. En cas de problème ou de retard de livraison, notre équipe intervient auprès du vendeur.
                    </p>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
