@extends('layouts.app')

@section('title', 'Mon compte')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $user = auth()->user();

    $lastOrder = $shippingOrders->first();
    $otherOrders = $shippingOrders->slice(1);
    $nextAppointment = $appointments->first();
    $inTransit = $shippingOrders->filter(fn ($o) => $o->shipping_status !== 'delivered')->count();

    // Phrase de situation construite uniquement à partir des données affichées.
    $parts = [];
    if ($inTransit > 0) {
        $parts[] = $inTransit > 1 ? "{$inTransit} colis sont en cours d'acheminement" : "un colis est en cours d'acheminement";
    }
    if ($nextAppointment) {
        $parts[] = 'votre prochain rendez-vous a lieu le '.$nextAppointment->reservation_datetime?->translatedFormat('l d F à H:i');
    }
    if ($downloads->isNotEmpty()) {
        $parts[] = 'vos produits digitaux sont prêts à être téléchargés';
    }
    $situation = $parts
        ? ucfirst(implode(', ', array_slice($parts, 0, -1)).(count($parts) > 1 ? ' et ' : '').end($parts)).'.'
        : 'Vos commandes, téléchargements et rendez-vous apparaîtront ici après votre premier achat.';

    $icon = fn ($d) => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.$d.'"/></svg>';
    $arrow = 'M5 12h14M13 6l6 6-6 6';
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Bonjour {{ $user->first_name }}</h1>
    <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-ink-2">{{ $situation }}</p>
</header>

<div class="mt-8 grid gap-5 lg:grid-cols-3">
    {{-- Dernière commande --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-6 lg:col-span-2" aria-labelledby="last-order-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="last-order-title" class="font-sans text-lg font-semibold text-ink">Votre dernier colis</h2>
            <a href="{{ route('account.orders') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
                Toutes mes commandes {!! $icon($arrow) !!}
            </a>
        </div>

        @if ($lastOrder)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
                <a href="{{ route('account.orders.show', $lastOrder) }}" class="font-mono text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">{{ $lastOrder->order_number }}</a>
                <span class="text-ink-2">
                    {{ $lastOrder->created_at->translatedFormat('d F Y') }} · <span class="font-semibold text-ink">{{ $m($lastOrder->total_amount) }} {{ $lastOrder->currency }}</span>
                </span>
            </div>
            <x-account.order-tracker :order="$lastOrder" class="mt-5" />

            @if ($otherOrders->isNotEmpty())
                <ul class="mt-6 divide-y divide-line border-t border-line">
                    @foreach ($otherOrders as $order)
                        <li>
                            <a href="{{ route('account.orders.show', $order) }}" class="flex min-h-11 flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3 text-sm hover:text-ink">
                                <span class="font-mono text-ink">{{ $order->order_number }}</span>
                                <span class="text-ink-2">{{ $order->created_at->translatedFormat('d/m/Y') }} · {{ $m($order->total_amount) }} {{ $order->currency }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            <p class="mt-3 text-sm leading-relaxed text-ink-2">Aucun colis en cours. Le suivi de vos commandes physiques s'affichera ici.</p>
        @endif
    </section>

    {{-- Prochain rendez-vous --}}
    @if ($nextAppointment)
        <section class="flex flex-col rounded-2xl bg-ink p-5 text-white sm:p-6" aria-labelledby="next-appointment-title">
            <h2 id="next-appointment-title" class="font-sans text-sm font-semibold text-accent-light">Prochain rendez-vous</h2>
            <p class="mt-3 text-base font-semibold leading-snug">{{ $nextAppointment->product_name }}</p>
            <p class="mt-3 font-display text-[28px] font-semibold leading-tight">
                {{ $nextAppointment->reservation_datetime?->translatedFormat('l d F') }}
            </p>
            <p class="text-[15px] text-white/80">
                à {{ $nextAppointment->reservation_datetime?->format('H:i') }}
                @if ($nextAppointment->product?->duration_minutes) · {{ $nextAppointment->product->duration_minutes }} min @endif
            </p>
            <p class="mt-1 text-sm text-white/70">
                @if ($nextAppointment->product?->delivery_mode === 'online' || $nextAppointment->product?->meeting_link)
                    En ligne
                @elseif ($nextAppointment->product?->location)
                    {{ $nextAppointment->product->location }}
                @endif
            </p>
            <div class="mt-auto flex flex-wrap gap-2 pt-5">
                @if ($nextAppointment->product?->meeting_link)
                    <a href="{{ $nextAppointment->product->meeting_link }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full bg-white px-5 text-sm font-semibold text-ink hover:bg-ground">Rejoindre</a>
                @endif
                <a href="{{ route('account.appointments') }}" class="inline-flex min-h-11 items-center rounded-full border border-white/30 px-5 text-sm font-semibold text-white hover:border-white">Mes rendez-vous</a>
            </div>
        </section>
    @else
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="next-appointment-title">
            <h2 id="next-appointment-title" class="font-sans text-lg font-semibold text-ink">Rendez-vous</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-2">Aucun rendez-vous à venir.</p>
            <a href="{{ route('account.appointments') }}" class="mt-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
                Voir l'historique {!! $icon($arrow) !!}
            </a>
        </section>
    @endif
</div>

{{-- Bibliothèque --}}
<section class="mt-12" aria-labelledby="library-title">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <h2 id="library-title" class="font-display text-[28px] font-semibold text-ink">Ma bibliothèque</h2>
        <a href="{{ route('account.downloads') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
            Tout voir {!! $icon($arrow) !!}
        </a>
    </div>

    @if ($downloads->isNotEmpty())
        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($downloads as $item)
                @php $exhausted = $item->max_downloads > 0 && (int) $item->download_count >= $item->max_downloads; @endphp
                <li class="flex flex-col rounded-2xl border border-line bg-surface p-5">
                    <p class="text-base font-semibold leading-snug text-ink">{{ $item->product_name }}</p>
                    <p class="mt-1 text-[13px] text-ink-3">
                        Acheté le {{ $item->order?->created_at?->format('d/m/Y') }}
                        @if ($item->max_downloads > 0) · {{ (int) $item->download_count }}/{{ $item->max_downloads }} téléchargements @endif
                    </p>
                    <div class="mt-4">
                        @if ($exhausted)
                            <span class="inline-flex min-h-11 items-center text-sm text-ink-3">Quota de téléchargements atteint</span>
                        @else
                            <x-ui.button :href="$item->download_url" variant="secondary">
                                {!! $icon('M12 4v11M7.5 10.5L12 15l4.5-4.5M5 19h14') !!}
                                Télécharger
                            </x-ui.button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <x-ui.empty-state class="mt-4" icon="download" title="Aucun produit digital pour l'instant" text="Les ebooks, formations et fichiers que vous achetez se retrouvent ici." />
    @endif
</section>

@unless ($user->isAffiliate())
    <aside class="mt-12 flex flex-col gap-4 rounded-2xl border border-line bg-ground-alt p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div>
            <h2 class="font-sans text-base font-semibold text-ink">Activer l'affiliation</h2>
            <p class="mt-1 max-w-xl text-sm leading-relaxed text-ink-2">Recommandez les produits digitaux éligibles et touchez 15 %, 10 % ou 5 % de commission selon le niveau.</p>
        </div>
        <x-ui.button :href="route('affiliate.join')" variant="secondary" class="shrink-0">En savoir plus</x-ui.button>
    </aside>
@endunless
@endsection
