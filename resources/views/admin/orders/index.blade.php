@extends('layouts.admin')

@section('title', 'Administration — Commandes')
@section('eyebrow', 'Ventes')
@section('heading', 'Commandes')

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $providerLabels = ['fedapay' => 'FedaPay', 'cinetpay' => 'CinetPay', 'cod' => 'À la livraison'];
    $statusMeta = [
        'pending' => ['En attente', 'muted'],
        'processing' => ['En cours', 'accent'],
        'completed' => ['Terminée', 'accent'],
        'cancelled' => ['Annulée', 'rust'],
        'refunded' => ['Remboursée', 'rust'],
    ];
@endphp

<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <x-ui.stat-tile label="COD à encaisser" :value="$kpi['cod_count']">
        <x-slot:foot>{{ $m($kpi['cod_amount']) }} en attente</x-slot:foot>
    </x-ui.stat-tile>
    <x-ui.stat-tile label="À préparer" :value="$kpi['to_prepare']" />
    <x-ui.stat-tile label="Encaissé aujourd'hui" :value="$m($kpi['revenue_today'])" />
    <x-ui.stat-tile label="Commandes" :value="$orders->total()" />
</div>

<form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
    <div class="min-w-[200px] flex-1">
        <x-ui.field name="q" label="Recherche" :value="$filters['q']" placeholder="N° commande, client…" />
    </div>
    <x-ui.select name="provider" label="Paiement" :selected="$filters['provider']" placeholder="Tous"
        :options="['fedapay' => 'FedaPay', 'cinetpay' => 'CinetPay', 'cod' => 'À la livraison']" />
    <x-ui.select name="payment_status" label="Statut paiement" :selected="$filters['paymentStatus']" placeholder="Tous"
        :options="['pending' => 'En attente', 'paid' => 'Payé', 'failed' => 'Échoué', 'refunded' => 'Remboursé']" />
    <x-ui.select name="status" label="Statut commande" :selected="$filters['status']" placeholder="Tous"
        :options="['pending' => 'En attente', 'processing' => 'En cours', 'completed' => 'Terminée', 'cancelled' => 'Annulée', 'refunded' => 'Remboursée']" />
    <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>
    @if (array_filter($filters))
        <a href="{{ route('admin.orders.index') }}" class="pb-2.5 text-sm text-ink-3 hover:text-ink">Réinitialiser</a>
    @endif
</form>

@if ($orders->isEmpty())
    <x-ui.empty-state title="Aucune commande" text="Aucune commande ne correspond à ces filtres." />
@else
    <div class="overflow-x-auto rounded-2xl border border-black/10">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                <tr>
                    <th class="px-4 py-3 font-semibold">Commande</th>
                    <th class="px-4 py-3 font-semibold">Client</th>
                    <th class="px-4 py-3 font-semibold">Montant</th>
                    <th class="px-4 py-3 font-semibold">Paiement</th>
                    <th class="px-4 py-3 font-semibold">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @foreach ($orders as $o)
                    <tr class="hover:bg-surface">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $o) }}" class="block font-mono text-[13px] font-medium text-ink hover:text-accent">{{ $o->order_number }}</a>
                            <span class="block text-xs text-ink-3">{{ $o->created_at?->format('d/m/Y H:i') }} · {{ $o->items->count() }} article(s)</span>
                        </td>
                        <td class="px-4 py-3 text-ink-2">
                            @if ($o->user)
                                {{ trim("{$o->user->first_name} {$o->user->last_name}") ?: $o->user->email }}
                                <span class="block text-xs text-ink-3">{{ $o->user->email }}</span>
                            @else
                                <span class="text-ink-3">Invité</span>
                                @if (data_get($o->shipping_address, 'phone'))
                                    <span class="block text-xs text-ink-3">{{ data_get($o->shipping_address, 'phone') }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-ink">{{ $m($o->total_amount) }} {{ $o->currency }}</td>
                        <td class="px-4 py-3">
                            <span class="text-ink-2">{{ $providerLabels[$o->payment_provider] ?? ($o->payment_provider ?: '—') }}</span>
                            <span class="mt-1 block"><x-ui.status-pill :status="$o->payment_status" /></span>
                        </td>
                        <td class="px-4 py-3">
                            @php [$sLabel, $sTone] = $statusMeta[$o->status] ?? [$o->status, 'muted']; @endphp
                            <x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge>
                            @if ($o->isCashOnDelivery() && $o->payment_status === 'pending' && $o->status !== 'cancelled')
                                <span class="mt-1 block text-[11px] font-semibold text-amber">À encaisser</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $o) }}" class="text-xs text-accent hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$orders" />
@endif
@endsection
