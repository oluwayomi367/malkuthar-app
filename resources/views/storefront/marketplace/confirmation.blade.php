@extends('layouts.storefront')

@section('title', 'Commande ' . $order->order_number)

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-2xl px-4 py-14 sm:px-6 text-center">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-green/15 text-brand-green">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h1 class="mt-4 font-display text-2xl font-bold text-ink">Commande enregistrée</h1>
    <p class="mt-2 text-ink-2">
        Votre commande <span class="font-mono font-semibold text-ink">{{ $order->order_number }}</span> a bien été prise en compte.
        @if ($order->payment_status !== 'paid') Le règlement se fera à la livraison / sur place. @endif
    </p>

    <div class="mt-6 space-y-3 text-left">
        @foreach ($order->suborders as $sub)
            <div class="rounded-2xl border border-line bg-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-3">{{ $sub->vendor->display_name }}</p>
                <ul class="mt-2 space-y-1 text-sm text-ink-2">
                    @foreach ($sub->items as $item)
                        <li class="flex justify-between"><span>{{ $item->quantity }} × {{ $item->title }}</span><span>{{ $money($item->line_total) }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex items-center justify-between rounded-xl border border-line bg-ground-alt/60 px-4 py-3 text-left">
        <span class="text-sm text-ink-2">Total</span>
        <span class="font-display text-lg font-bold text-ink">{{ $money($order->grand_total) }} {{ $order->currency }}</span>
    </div>

    <div class="mt-6"><x-ui.button variant="secondary" :href="route('marketplace.shop')">Continuer mes achats</x-ui.button></div>
</div>
@endsection
