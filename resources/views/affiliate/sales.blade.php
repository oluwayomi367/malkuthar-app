@extends('layouts.app')

@section('title', 'Mes ventes')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $statusTone = ['pending' => 'processing', 'available' => 'available', 'paid' => 'paid', 'cancelled' => 'cancelled', 'reversed' => 'reversed'];
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes ventes</h1>
    <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-ink-2">Une ligne par commission générée par votre réseau. Les clients sont anonymisés.</p>
</header>

@if ($sales->isEmpty())
    <x-ui.empty-state class="mt-8" icon="cart" title="Aucune vente pour le moment" text="Partagez votre lien d'affiliation pour générer vos premières commissions.">
        <x-slot:cta>
            <x-ui.button :href="route('affiliate.dashboard')" variant="primary">Voir mon lien</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <div class="mt-8 overflow-x-auto rounded-2xl border border-line bg-surface">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-line text-[13px] text-ink-3">
                <tr>
                    <th scope="col" class="px-5 py-3 font-semibold">Produit</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Date</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Montant</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Client</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Niveau</th>
                    <th scope="col" class="px-5 py-3 text-right font-semibold">Commission</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($sales as $s)
                    <tr class="hover:bg-ground">
                        <td class="px-5 py-3.5">
                            <span class="font-medium text-ink">{{ $s['product'] }}</span>
                            <span class="mt-0.5 block font-mono text-xs text-ink-3">{{ $s['reference'] }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-ink-2">{{ optional($s['date'])->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-ink-2">{{ $money($s['amount']) }} {{ $s['currency'] }}</td>
                        <td class="px-5 py-3.5 text-ink-2">{{ $s['client'] }}</td>
                        <td class="px-5 py-3.5 text-ink">{{ $s['level'] }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-ink">{{ $money($s['commission']) }} {{ $s['currency'] }}</td>
                        <td class="px-5 py-3.5"><x-ui.status-pill :status="$statusTone[$s['status']] ?? 'processing'" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$sales" />
@endif
@endsection
