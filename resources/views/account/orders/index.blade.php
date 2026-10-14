@extends('layouts.app')

@section('title', 'Mes commandes')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes commandes</h1>
    <p class="mt-2 text-[15px] text-ink-2">Historique complet de vos achats.</p>
</header>

@if ($orders->isEmpty())
    <x-ui.empty-state
        class="mt-8"
        icon="cart"
        title="Aucune commande"
        text="Vos achats apparaîtront ici dès votre première commande.">
        <x-slot:cta>
            <x-ui.button :href="route('catalog.index')" variant="primary">Explorer la boutique</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    {{-- Mobile : cartes --}}
    <ul class="mt-8 space-y-3 md:hidden">
        @foreach ($orders as $order)
            <li>
                <a href="{{ route('account.orders.show', $order) }}" class="block rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-mono text-sm text-ink">{{ $order->order_number }}</span>
                        <x-ui.status-pill :status="$order->payment_status" />
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
                        <span class="text-ink-2">{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->items_count }} article{{ $order->items_count > 1 ? 's' : '' }}</span>
                        <span class="font-semibold text-ink">{{ $m($order->total_amount) }} {{ $order->currency }}</span>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Bureau : tableau --}}
    <div class="mt-8 hidden overflow-x-auto rounded-2xl border border-line bg-surface md:block">
        <table class="w-full text-sm">
            <thead class="border-b border-line text-left text-[13px] text-ink-3">
                <tr>
                    <th scope="col" class="px-5 py-3 font-semibold">Commande</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Date</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Articles</th>
                    <th scope="col" class="px-5 py-3 font-semibold">Paiement</th>
                    <th scope="col" class="px-5 py-3 text-right font-semibold">Total</th>
                    <th scope="col" class="px-5 py-3"><span class="sr-only">Détail</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($orders as $order)
                    <tr class="hover:bg-ground">
                        <td class="px-5 py-3.5 font-mono text-ink">{{ $order->order_number }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-ink-2">{{ $order->created_at->translatedFormat('d F Y') }}</td>
                        <td class="px-5 py-3.5 text-ink-2">{{ $order->items_count }}</td>
                        <td class="px-5 py-3.5"><x-ui.status-pill :status="$order->payment_status" /></td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-ink">{{ $m($order->total_amount) }} {{ $order->currency }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('account.orders.show', $order) }}" class="inline-flex min-h-11 items-center font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">
                                Voir<span class="sr-only"> la commande {{ $order->order_number }}</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<x-ui.pagination :paginator="$orders" />
@endsection
