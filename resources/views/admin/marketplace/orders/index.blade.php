@extends('layouts.admin')

@section('title', 'Administration — Commandes Marketplace')
@section('eyebrow', 'Marketplace')
@section('heading', 'Commandes')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<nav class="mb-5 flex flex-wrap gap-2 text-sm">
    @foreach (['' => 'Toutes', 'pending' => 'À encaisser', 'paid' => 'Payées', 'failed' => 'Annulées'] as $k => $lbl)
        <a href="{{ route('admin.marketplace.orders.index', array_filter(['payment_status' => $k])) }}"
           @class(['rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => request('payment_status', '') === $k,
               'border-black/10 text-ink-2 hover:text-ink' => request('payment_status', '') !== $k])>
            {{ $lbl }}@if (isset($counts[$k])) <span class="text-ink-3">({{ $counts[$k] }})</span>@endif
        </a>
    @endforeach
</nav>

@if ($orders->isEmpty())
    <x-ui.empty-state title="Aucune commande" text="Aucune commande pour ce filtre." />
@else
    <div class="space-y-3">
        @foreach ($orders as $order)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.marketplace.orders.show', $order) }}" class="font-mono font-semibold text-ink hover:text-accent">{{ $order->order_number }}</a>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $order->buyer_email }} · {{ $order->suborders_count }} vendeur(s) · {{ $order->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-ink">{{ $money($order->grand_total) }} {{ $order->currency }}</span>
                        <x-ui.status-pill :status="$order->payment_status" />
                    </div>
                </div>
                @if ($order->payment_status === 'pending')
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-black/[0.06] pt-3">
                        <form method="POST" action="{{ route('admin.marketplace.orders.mark-paid', $order) }}">@csrf
                            <button class="h-9 rounded-lg border border-brand-green/40 bg-brand-green/10 px-3 text-xs font-semibold text-brand-green hover:bg-brand-green/20">Encaisser</button>
                        </form>
                        <form method="POST" action="{{ route('admin.marketplace.orders.cancel', $order) }}" class="flex gap-1">@csrf
                            <input name="reason" required placeholder="Motif d'annulation" class="h-9 w-52 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust">Annuler</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$orders" class="mt-6" />
@endif
@endsection
