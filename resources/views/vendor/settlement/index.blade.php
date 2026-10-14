@extends('layouts.app')

@section('title', 'Mes revenus')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $labels = [
        'sale' => 'Vente', 'refund_reversal' => 'Reprise après remboursement', 'payout' => 'Versement',
        'payout_reversal' => 'Reprise de versement', 'adjustment' => 'Ajustement', 'chargeback' => 'Rétrofacturation',
    ];
@endphp

<header class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes revenus</h1>
        <p class="mt-2 text-[15px] text-ink-2">Solde reconstitué à partir du registre. Chaque opération porte une référence.</p>
    </div>
    <x-ui.button variant="primary" :href="route('vendor.payouts.index')">Demander un versement</x-ui.button>
</header>

<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <x-ui.stat-tile label="Disponible" :value="$money($balance->available_amount)" :currency="$balance->currency" emphasis />
    <x-ui.stat-tile label="En attente de validation" :value="$money($balance->pending_amount)" :currency="$balance->currency" />
    <x-ui.stat-tile label="Réservé (versement en cours)" :value="$money($balance->reserved_amount)" :currency="$balance->currency" />
</div>

<dl class="mt-4 grid divide-y divide-line rounded-2xl border border-line bg-surface text-sm sm:grid-cols-3 sm:divide-x sm:divide-y-0">
    <div class="px-5 py-4"><dt class="text-ink-2">Total gagné</dt><dd class="mt-1 text-base font-semibold text-ink">{{ $money($balance->lifetime_earned) }} {{ $balance->currency }}</dd></div>
    <div class="px-5 py-4"><dt class="text-ink-2">Total versé</dt><dd class="mt-1 text-base font-semibold text-ink">{{ $money($balance->lifetime_paid) }} {{ $balance->currency }}</dd></div>
    <div class="px-5 py-4"><dt class="text-ink-2">Total repris</dt><dd class="mt-1 text-base font-semibold text-ink">{{ $money($balance->lifetime_reversed) }} {{ $balance->currency }}</dd></div>
</dl>

<section class="mt-12" aria-labelledby="ledger-title">
    <h2 id="ledger-title" class="font-display text-[28px] font-semibold text-ink">Opérations</h2>
    <div class="mt-4 overflow-x-auto rounded-2xl border border-line bg-surface">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="border-b border-line text-left text-[13px] text-ink-3">
                <tr>
                    <th scope="col" class="px-5 py-3 font-semibold">Date</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Opération</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Référence</th>
                    <th scope="col" class="px-5 py-3 text-right font-semibold">Montant</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($entries as $e)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-3.5 text-ink-2">{{ $e->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5 text-ink">{{ $labels[$e->source_type] ?? $e->source_type }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-ink-3">{{ \Illuminate\Support\Str::limit($e->reference, 28) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold {{ $e->direction === 'credit' ? 'text-sage' : 'text-ink-2' }}">
                            <span class="sr-only">{{ $e->direction === 'credit' ? 'Crédit' : 'Débit' }} :</span>
                            {{ $e->direction === 'credit' ? '+' : '−' }}{{ $money($e->amount) }}
                        </td>
                        <td class="px-5 py-3.5"><x-ui.status-pill :status="$e->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-ink-2">Aucune opération pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$entries" />
</section>
@endsection
