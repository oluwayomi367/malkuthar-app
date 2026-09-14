@extends('layouts.app')

@section('title', 'Mon compte')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Bonjour {{ auth()->user()->first_name }}</h1>
<p class="mt-1 text-sm text-ink-2">Vos téléchargements, vos colis et vos rendez-vous.</p>

@unless (auth()->user()->isAffiliate())
    <div class="mt-4">
        <a href="{{ route('affiliate.join') }}" class="inline-flex items-center gap-2 rounded-xl border border-accent/30 bg-accent/10 px-4 py-2 text-sm font-semibold text-accent">
            ✦ Devenir affilié — gagnez 15 · 10 · 5 %
        </a>
    </div>
@endunless

{{-- Téléchargements --}}
<section class="mt-10">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-ink">Mes produits digitaux</h2>
        <a href="{{ route('account.downloads') }}" class="text-sm text-ink-2 hover:text-ink">Tout voir →</a>
    </div>
    @forelse ($downloads as $item)
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-black/10 bg-surface px-4 py-3">
            <div>
                <p class="text-[15px] font-medium text-ink">{{ $item->product_name }}</p>
                <p class="text-xs text-ink-3">
                    Acheté le {{ $item->order?->created_at?->format('d/m/Y') }}
                    @if ($item->max_downloads > 0) · {{ $item->download_count }}/{{ $item->max_downloads }} téléchargements @endif
                </p>
            </div>
            <a href="{{ $item->download_url }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3.5 text-sm font-semibold text-white">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 2v8m0 0L5 7m3 3l3-3M3 13h10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Télécharger
            </a>
        </div>
    @empty
        <p class="rounded-xl border border-dashed border-black/10 bg-surface px-4 py-6 text-center text-sm text-ink-3">Aucun produit digital pour l'instant.</p>
    @endforelse
</section>

{{-- Colis --}}
<section class="mt-8">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-ink">Mes colis</h2>
        <a href="{{ route('account.orders') }}" class="text-sm text-ink-2 hover:text-ink">Toutes mes commandes →</a>
    </div>
    @forelse ($shippingOrders as $order)
        <a href="{{ route('account.orders.show', $order) }}" class="mb-3 block rounded-xl border border-black/10 bg-surface p-4 transition-colors hover:border-black/20">
            <div class="flex items-center justify-between text-sm">
                <span class="font-mono text-ink">{{ $order->order_number }}</span>
                <span class="text-ink-3">{{ $m($order->total_amount) }} {{ $order->currency }}</span>
            </div>
            <x-account.order-tracker :order="$order" class="mt-3" />
        </a>
    @empty
        <p class="rounded-xl border border-dashed border-black/10 bg-surface px-4 py-6 text-center text-sm text-ink-3">Aucun colis en cours.</p>
    @endforelse
</section>

{{-- Rendez-vous --}}
<section class="mt-8">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-ink">Mes rendez-vous</h2>
        <a href="{{ route('account.appointments') }}" class="text-sm text-ink-2 hover:text-ink">Tout voir →</a>
    </div>
    @forelse ($appointments as $item)
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-black/10 bg-surface px-4 py-3">
            <div>
                <p class="text-[15px] font-medium text-ink">{{ $item->product_name }}</p>
                <p class="text-xs text-ink-3">{{ $item->reservation_datetime?->translatedFormat('l d F Y · H:i') }}</p>
            </div>
            @if ($item->product?->meeting_link)
                <a href="{{ $item->product->meeting_link }}" class="inline-flex h-9 items-center rounded-lg border border-black/10 bg-black/[0.04] px-3.5 text-sm font-semibold text-ink hover:bg-black/[0.06]">Rejoindre</a>
            @endif
        </div>
    @empty
        <p class="rounded-xl border border-dashed border-black/10 bg-surface px-4 py-6 text-center text-sm text-ink-3">Aucun rendez-vous à venir.</p>
    @endforelse
</section>
@endsection
