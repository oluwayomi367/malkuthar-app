@extends('layouts.app')

@section('title', 'Mes ventes')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $statusTone = ['pending' => 'processing', 'available' => 'available', 'paid' => 'paid', 'cancelled' => 'cancelled', 'reversed' => 'reversed'];
    $levelAccent = [1 => 'text-accent', 2 => 'text-emerald-300', 3 => 'text-amber'];
@endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace affilié</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes ventes</h1>
<p class="mt-1 text-sm text-ink-2">Une ligne par commission générée par votre réseau. Les clients sont anonymisés (§45).</p>

@if ($sales->isEmpty())
    <x-ui.empty-state class="mt-8" title="Aucune vente pour le moment" text="Partagez votre lien d'affiliation pour générer vos premières commissions.">
        <x-slot:cta>
            <x-ui.button :href="route('affiliate.dashboard')" variant="primary">Voir mon lien</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <div class="mt-8 overflow-x-auto rounded-2xl border border-black/10">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                <tr>
                    <th class="px-4 py-3 font-semibold">Produit</th>
                    <th class="px-4 py-3 font-semibold">Date</th>
                    <th class="px-4 py-3 font-semibold">Montant</th>
                    <th class="px-4 py-3 font-semibold">Client</th>
                    <th class="px-4 py-3 font-semibold">Niv.</th>
                    <th class="px-4 py-3 font-semibold">Commission</th>
                    <th class="px-4 py-3 font-semibold">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @foreach ($sales as $s)
                    <tr class="hover:bg-surface">
                        <td class="px-4 py-3">
                            <span class="text-ink">{{ $s['product'] }}</span>
                            <span class="block font-mono text-[10.5px] text-ink-3">{{ $s['reference'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-3">{{ optional($s['date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-ink-2">{{ $money($s['amount']) }} {{ $s['currency'] }}</td>
                        <td class="px-4 py-3 text-ink-3">{{ $s['client'] }}</td>
                        <td class="px-4 py-3 font-semibold {{ $levelAccent[$s['level']] ?? 'text-ink-2' }}">{{ $s['level'] }}</td>
                        <td class="px-4 py-3 font-medium text-ink">{{ $money($s['commission']) }} {{ $s['currency'] }}</td>
                        <td class="px-4 py-3"><x-ui.status-pill :status="$statusTone[$s['status']] ?? 'processing'" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$sales" />
@endif
@endsection
