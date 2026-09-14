@extends('layouts.app')

@section('title', 'Mes revenus')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $labels = [
        'sale' => 'Vente', 'refund_reversal' => 'Reprise remb.', 'payout' => 'Versement',
        'payout_reversal' => 'Reprise versement', 'adjustment' => 'Ajustement', 'chargeback' => 'Rétrofacturation',
    ];
@endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes revenus</h1>
<p class="mt-1 text-sm text-ink-2">Solde reconstitué à partir du registre. Chaque opération porte une référence.</p>

<div class="mt-5 grid gap-3 sm:grid-cols-3">
    <x-ui.stat-tile label="Disponible" :value="$money($balance->available_amount)" :currency="$balance->currency" emphasis />
    <x-ui.stat-tile label="En attente de validation" :value="$money($balance->pending_amount)" :currency="$balance->currency" />
    <x-ui.stat-tile label="Réservé (versement en cours)" :value="$money($balance->reserved_amount)" :currency="$balance->currency" />
</div>
<div class="mt-3 grid gap-3 sm:grid-cols-3 text-sm text-ink-2">
    <div class="rounded-xl border border-line bg-surface px-4 py-3">Total gagné <span class="block font-semibold text-ink">{{ $money($balance->lifetime_earned) }}</span></div>
    <div class="rounded-xl border border-line bg-surface px-4 py-3">Total versé <span class="block font-semibold text-ink">{{ $money($balance->lifetime_paid) }}</span></div>
    <div class="rounded-xl border border-line bg-surface px-4 py-3">Total repris <span class="block font-semibold text-ink">{{ $money($balance->lifetime_reversed) }}</span></div>
</div>

<div class="mt-4">
    <x-ui.button variant="primary" :href="route('vendor.payouts.index')">Demander un versement</x-ui.button>
</div>

<div class="mt-8 overflow-x-auto rounded-2xl border border-line bg-surface">
    <table class="w-full text-sm">
        <thead class="bg-ground-alt/60 text-left text-[11px] uppercase tracking-wide text-ink-3">
            <tr><th class="px-4 py-2">Date</th><th class="px-4 py-2">Opération</th><th class="px-4 py-2">Référence</th><th class="px-4 py-2 text-right">Montant</th><th class="px-4 py-2">Statut</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($entries as $e)
                <tr>
                    <td class="px-4 py-2 text-ink-2">{{ $e->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-ink">{{ $labels[$e->source_type] ?? $e->source_type }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-ink-3">{{ \Illuminate\Support\Str::limit($e->reference, 28) }}</td>
                    <td class="px-4 py-2 text-right {{ $e->direction === 'credit' ? 'text-brand-green' : 'text-ink-2' }}">
                        {{ $e->direction === 'credit' ? '+' : '−' }}{{ $money($e->amount) }}
                    </td>
                    <td class="px-4 py-2"><x-ui.status-pill :status="$e->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-ink-3">Aucune opération.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-ui.pagination :paginator="$entries" class="mt-6" />
@endsection
