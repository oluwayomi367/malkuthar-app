@extends('layouts.app')

@section('title', 'Mes commandes')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes commandes</h1>

@if ($suborders->isEmpty())
    <x-ui.empty-state class="mt-6" title="Aucune commande" text="Vos ventes apparaîtront ici." />
@else
    <div class="mt-6 space-y-3">
        @foreach ($suborders as $sub)
            <a href="{{ route('vendor.orders.show', $sub) }}" class="block rounded-2xl border border-line bg-surface p-4 hover:border-line-strong">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-ink">{{ $sub->suborder_number }}</p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $sub->items->sum('quantity') }} article(s) ·
                            {{ optional($sub->order->paid_at)->format('d/m/Y') ?? 'en attente de paiement' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-ink">Net {{ $money($sub->vendor_net) }}</p>
                        <div class="mt-1 flex justify-end gap-1">
                            <x-ui.status-pill :status="$sub->fulfilment_status" />
                            <x-ui.status-pill :status="$sub->settlement_status" />
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$suborders" class="mt-6" />
@endif
@endsection
