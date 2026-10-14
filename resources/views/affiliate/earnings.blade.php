@extends('layouts.app')

@section('title', 'Mes gains')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $sourceLabel = [
        'commission' => 'Commission', 'commission_reversal' => 'Reprise',
        'withdrawal' => 'Retrait', 'withdrawal_failed_refund' => 'Remboursement de retrait', 'adjustment' => 'Ajustement',
    ];
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes gains</h1>
    <p class="mt-2 text-[15px] text-ink-2">Historique financier complet. Chaque opération porte une référence unique.</p>
</header>

{{-- §48 — le solde est reconstitué à partir du registre, pas d'une variable --}}
<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <x-ui.stat-tile label="Solde disponible" :value="$money($ledgerBalance)" :currency="$currency" :emphasis="true">
        <x-slot:foot>Reconstitué depuis le registre.</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="Total commissions" :value="$money($totalEarned)" :currency="$currency" />
    <x-ui.stat-tile label="Total retiré" :value="$money($totalWithdrawn)" :currency="$currency" />
</div>

<form method="GET" class="mt-8 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="filters-title">
    <h2 id="filters-title" class="font-sans text-base font-semibold text-ink">Filtrer les opérations</h2>
    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-ui.field name="from" label="Du" type="date" :value="$filters['from'] ?? ''" />
        <x-ui.field name="to" label="Au" type="date" :value="$filters['to'] ?? ''" />
        <x-ui.select name="level" label="Niveau" :selected="$filters['level'] ?? ''" placeholder="Tous"
            :options="[1 => 'Niveau 1', 2 => 'Niveau 2', 3 => 'Niveau 3']" />
        <x-ui.select name="product" label="Produit" :selected="$filters['product'] ?? ''" placeholder="Tous"
            :options="$products->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" />
        <x-ui.select name="status" label="Statut" :selected="$filters['status'] ?? ''" placeholder="Tous"
            :options="['pending' => 'En attente', 'available' => 'Disponible', 'processing' => 'En traitement', 'paid' => 'Payé', 'cancelled' => 'Annulé', 'reversed' => 'Repris', 'failed' => 'Échoué']" />
        <x-ui.field name="min_amount" label="Montant min." type="number" :value="$filters['min_amount'] ?? ''" />
    </div>
    <div class="mt-5 flex flex-wrap items-center gap-4">
        <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>
        @if (array_filter($filters))
            <a href="{{ route('affiliate.earnings') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-ink-2 underline decoration-line-strong underline-offset-4 hover:text-ink">Réinitialiser</a>
        @endif
    </div>
</form>

@if ($entries->isEmpty())
    <x-ui.empty-state class="mt-6" icon="wallet" title="Aucune opération sur cette période." />
@else
    <ul class="mt-6 divide-y divide-line rounded-2xl border border-line bg-surface">
        @foreach ($entries as $e)
            <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-4 text-sm">
                <div class="min-w-0">
                    <p class="text-[15px] font-medium text-ink">
                        {{ $sourceLabel[$e->source_type] ?? $e->source_type }}
                        @if ($e->orderItem?->product_name)<span class="font-normal text-ink-2"> — {{ $e->orderItem->product_name }}</span>@endif
                    </p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-ink-3">
                        <span class="font-mono">{{ $e->reference }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $e->created_at->format('d/m/Y') }}</span>
                        @if ($e->level)<span aria-hidden="true">·</span><span>Niveau {{ $e->level }}</span>@endif
                        <x-ui.status-pill :status="$e->status" class="ml-1" />
                    </p>
                </div>
                <p class="shrink-0 whitespace-nowrap text-[15px] font-semibold {{ $e->direction === 'credit' ? 'text-sage' : 'text-rust' }}">
                    <span class="sr-only">{{ $e->direction === 'credit' ? 'Crédit' : 'Débit' }} :</span>
                    {{ $e->direction === 'credit' ? '+' : '−' }}{{ $money($e->amount) }} {{ $e->currency }}
                </p>
            </li>
        @endforeach
    </ul>
    <x-ui.pagination :paginator="$entries" />
@endif
@endsection
