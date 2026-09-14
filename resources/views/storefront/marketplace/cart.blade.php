@extends('layouts.storefront')

@section('title', 'Mon panier — Place de marché')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
    <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mon panier</h1>

    @if ($lines->isEmpty())
        <x-ui.empty-state class="mt-6" title="Votre panier est vide"
            text="Parcourez la place de marché pour ajouter des articles." />
        <div class="mt-4"><x-ui.button variant="secondary" :href="route('marketplace.shop')">Voir les offres</x-ui.button></div>
    @else
        <div class="mt-6 space-y-6">
            @foreach ($byVendor as $vendorName => $vendorLines)
                <div class="rounded-2xl border border-line bg-surface p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-3">{{ $vendorName }}</p>
                    <div class="mt-2 divide-y divide-line">
                        @foreach ($vendorLines as $line)
                            <div class="flex items-center gap-3 py-3">
                                @if ($line['variant']?->image_path ?? $line['offer']->main_image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($line['variant']?->image_path ?? $line['offer']->main_image_path) }}"
                                         alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('marketplace.shop.offer', $line['offer']->slug) }}" class="line-clamp-1 font-medium text-ink hover:text-accent">{{ $line['offer']->title }}</a>
                                    @if ($line['variant'])<p class="text-xs text-ink-3">{{ $line['variant']->optionsLabel() }}</p>@endif
                                    <p class="text-xs text-ink-3">{{ $money($line['unit_price']) }} {{ $line['offer']->currency }} / unité</p>
                                </div>
                                <form method="POST" action="{{ route('marketplace.cart.update') }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                    <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="0" max="50"
                                           class="h-9 w-16 rounded-lg border border-line bg-surface px-2 text-sm"
                                           onchange="this.form.submit()">
                                </form>
                                <span class="w-24 text-right text-sm font-semibold text-ink">{{ $money($line['line_total']) }}</span>
                                <form method="POST" action="{{ route('marketplace.cart.remove') }}">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="line_key" value="{{ $line['key'] }}">
                                    <button class="text-ink-3 hover:text-rust" aria-label="Retirer">&times;</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-line pt-4">
            <span class="text-sm text-ink-2">Sous-total</span>
            <span class="font-display text-xl font-bold text-ink">{{ $money($subtotal) }}</span>
        </div>
        <div class="mt-4 flex justify-end">
            <x-ui.button variant="primary" :href="route('marketplace.checkout')">Passer la commande</x-ui.button>
        </div>
    @endif
</div>
@endsection
