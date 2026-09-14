@extends('layouts.app')

@section('title', 'Mes commandes')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes commandes</h1>
<p class="mt-1 text-sm text-ink-2">Historique complet de vos achats.</p>

<div class="mt-8 space-y-2">
    @forelse ($orders as $order)
        <a href="{{ route('account.orders.show', $order) }}" class="block rounded-xl border border-black/10 bg-surface p-4 transition-colors hover:border-black/20">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <span class="font-mono text-sm text-ink">{{ $order->order_number }}</span>
                <x-ui.status-pill :status="$order->payment_status" />
            </div>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm text-ink-3">
                <span>{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->items_count }} article{{ $order->items_count > 1 ? 's' : '' }}</span>
                <span class="font-medium text-ink">{{ $m($order->total_amount) }} {{ $order->currency }}</span>
            </div>
        </a>
    @empty
        <x-ui.empty-state
            title="Aucune commande"
            text="Vos achats apparaîtront ici dès votre première commande.">
            <x-slot:cta>
                <x-ui.button :href="route('catalog.index')" variant="primary">Explorer la boutique</x-ui.button>
            </x-slot:cta>
        </x-ui.empty-state>
    @endforelse
</div>

<x-ui.pagination :paginator="$orders" />
@endsection
