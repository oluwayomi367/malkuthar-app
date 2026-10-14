@extends('layouts.app')

@section('title', 'Mes commandes')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $isToProcess = fn ($s) => in_array($s->fulfilment_status, ['unfulfilled', 'processing'], true)
        && $s->order?->payment_status === 'paid';
    $toProcess = $suborders->getCollection()->filter($isToProcess);
    $others = $suborders->getCollection()->reject($isToProcess);
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes commandes</h1>
    <p class="mt-2 text-[15px] text-ink-2">Les commandes payées sont à préparer puis à marquer comme expédiées ou livrées.</p>
</header>

@if ($suborders->isEmpty())
    <x-ui.empty-state class="mt-8" icon="inbox" title="Aucune commande" text="Vos ventes apparaîtront ici." />
@else
    {{-- À traiter --}}
    <section class="mt-8 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="todo-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="todo-title" class="font-sans text-lg font-semibold text-ink">À traiter</h2>
            <span class="text-sm text-ink-2">{{ $toProcess->count() }} sur cette page</span>
        </div>

        @if ($toProcess->isEmpty())
            <p class="mt-2 text-sm leading-relaxed text-ink-2">Aucune commande en attente de préparation.</p>
        @else
            <ul class="mt-3 divide-y divide-line border-t border-line">
                @foreach ($toProcess as $sub)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3.5">
                        <div class="min-w-0">
                            <p class="font-mono text-sm text-ink">{{ $sub->suborder_number }}</p>
                            <p class="mt-0.5 text-[13px] text-ink-3">
                                {{ $sub->items->sum('quantity') }} article(s) · payée le {{ optional($sub->order->paid_at)->format('d/m/Y') }}
                                · Net <span class="font-semibold text-ink">{{ $money($sub->vendor_net) }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.status-pill :status="$sub->fulfilment_status" />
                            <a href="{{ route('vendor.orders.show', $sub) }}" class="inline-flex min-h-11 items-center rounded-full bg-space-vendor px-5 text-sm font-semibold text-white transition-opacity hover:opacity-90">
                                Traiter<span class="sr-only"> la commande {{ $sub->suborder_number }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Autres commandes --}}
    @if ($others->isNotEmpty())
        <section class="mt-10" aria-labelledby="others-title">
            <h2 id="others-title" class="font-display text-[28px] font-semibold text-ink">Toutes les commandes</h2>
            <div class="mt-4 overflow-x-auto rounded-2xl border border-line bg-surface">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="border-b border-line text-[13px] text-ink-3">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Commande</th>
                            <th scope="col" class="px-3 py-3 font-medium">Paiement</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Net</th>
                            <th scope="col" class="px-3 py-3 font-medium">Préparation</th>
                            <th scope="col" class="px-5 py-3 font-medium">Règlement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($others as $sub)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('vendor.orders.show', $sub) }}" class="inline-flex min-h-11 flex-col justify-center font-mono text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">{{ $sub->suborder_number }}</a>
                                    <span class="block text-[13px] text-ink-3">{{ $sub->items->sum('quantity') }} article(s)</span>
                                </td>
                                <td class="px-3 py-3 text-ink-2">{{ optional($sub->order->paid_at)->format('d/m/Y') ?? 'en attente de paiement' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-ink">{{ $money($sub->vendor_net) }}</td>
                                <td class="px-3 py-3"><x-ui.status-pill :status="$sub->fulfilment_status" /></td>
                                <td class="px-5 py-3"><x-ui.status-pill :status="$sub->settlement_status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <x-ui.pagination :paginator="$suborders" class="mt-6" />
@endif
@endsection
