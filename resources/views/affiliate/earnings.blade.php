@extends('layouts.app')

@section('title', 'Mes gains')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $sourceLabel = [
        'commission' => 'Commission', 'commission_reversal' => 'Reprise',
        'withdrawal' => 'Retrait', 'withdrawal_failed_refund' => 'Remb. retrait', 'adjustment' => 'Ajustement',
    ];
@endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace affilié</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes gains</h1>
<p class="mt-1 text-sm text-ink-2">Historique financier complet. Chaque opération porte une référence unique.</p>

{{-- §48 — le solde est reconstitué à partir du registre, pas d'une variable --}}
<div class="mt-5 grid gap-3 sm:grid-cols-3">
    <x-ui.stat-tile label="Solde disponible" :value="$money($ledgerBalance)" :currency="$currency" :emphasis="true">
        <x-slot:foot>Reconstitué depuis le registre.</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Total commissions" :value="$money($totalEarned)" :currency="$currency" />
    <x-ui.stat-tile label="Total retiré" :value="$money($totalWithdrawn)" :currency="$currency" />
</div>

<form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-black/10 bg-surface p-4 sm:grid-cols-2 lg:grid-cols-6">
    <x-ui.field name="from" label="Du" type="date" :value="$filters['from'] ?? ''" />
    <x-ui.field name="to" label="Au" type="date" :value="$filters['to'] ?? ''" />
    <x-ui.select name="level" label="Niveau" :selected="$filters['level'] ?? ''" placeholder="Tous"
        :options="[1 => 'Niveau 1', 2 => 'Niveau 2', 3 => 'Niveau 3']" />
    <x-ui.select name="product" label="Produit" :selected="$filters['product'] ?? ''" placeholder="Tous"
        :options="$products->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" />
    <x-ui.select name="status" label="Statut" :selected="$filters['status'] ?? ''" placeholder="Tous"
        :options="['pending' => 'En attente', 'available' => 'Disponible', 'processing' => 'En traitement', 'paid' => 'Payé', 'cancelled' => 'Annulé', 'reversed' => 'Repris', 'failed' => 'Échoué']" />
    <x-ui.field name="min_amount" label="Montant min." type="number" :value="$filters['min_amount'] ?? ''" />
    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
        <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>
        @if (array_filter($filters))
            <a href="{{ route('affiliate.earnings') }}" class="text-sm text-ink-3 hover:text-ink">Réinitialiser</a>
        @endif
    </div>
</form>

@if ($entries->isEmpty())
    <p class="mt-8 rounded-xl border border-dashed border-black/10 bg-surface px-4 py-8 text-center text-sm text-ink-3">Aucune opération sur cette période.</p>
@else
    <ul class="mt-6 divide-y divide-black/[0.06] rounded-2xl border border-black/10">
        @foreach ($entries as $e)
            <li class="flex items-center justify-between gap-4 px-4 py-3.5 text-sm">
                <div class="min-w-0">
                    <p class="font-mono text-xs text-ink-3">{{ $e->reference }}</p>
                    <p class="mt-0.5 text-ink">
                        {{ $sourceLabel[$e->source_type] ?? $e->source_type }}
                        @if ($e->orderItem?->product_name)<span class="text-ink-2">— {{ $e->orderItem->product_name }}</span>@endif
                        @if ($e->level)<span class="text-ink-3"> · niv. {{ $e->level }}</span>@endif
                        <span class="text-ink-3">· {{ $e->created_at->format('d/m/Y') }} · {{ $e->status }}</span>
                    </p>
                </div>
                <p class="shrink-0 font-display font-semibold {{ $e->direction === 'credit' ? 'text-brand-green' : 'text-rust' }}">
                    {{ $e->direction === 'credit' ? '+' : '−' }}{{ $money($e->amount) }} {{ $e->currency }}
                </p>
            </li>
        @endforeach
    </ul>
    <x-ui.pagination :paginator="$entries" />
@endif
@endsection
