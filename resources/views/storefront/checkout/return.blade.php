@extends('layouts.storefront')

@section('title', 'Confirmation de paiement')
@section('seo_description', 'Statut de votre commande MALKUTHAR.')

@section('content')
@php
    $isCod = $order->payment_provider === 'cod' && $status !== 'paid';
    $map = [
        'paid'     => ['Paiement confirmé', 'Votre commande est validée. Vous allez recevoir un email avec vos accès.', 'ok'],
        'pending'  => ['Paiement en cours de vérification', 'Nous confirmons votre paiement auprès du prestataire. Cette page se met à jour toute seule.', 'wait'],
        'failed'   => ['Paiement non abouti', "Le paiement n'a pas pu être confirmé. Vous pouvez réessayer.", 'bad'],
        'refunded' => ['Commande remboursée', 'Cette commande a été remboursée.', 'neut'],
    ];
    [$title, $text, $tone] = $isCod
        ? ['Commande enregistrée', "Vous réglerez à la livraison (ou lors du rendez-vous). Nous préparons votre commande et vous recontactons.", 'neut']
        : ($map[$status] ?? $map['pending']);
    $ring = ['ok' => 'border-emerald-400/40 text-emerald-300', 'wait' => 'border-amber/40 text-amber', 'bad' => 'border-rust/40 text-rust', 'neut' => 'border-black/15 text-ink-2'][$tone];
@endphp

<div class="relative mx-auto max-w-md px-4 py-16 text-center">
    <div class="relative">
        <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border {{ $ring }} bg-surface">
            @if ($tone === 'ok')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @elseif ($tone === 'bad')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7l10 10M17 7L7 17" stroke-linecap="round"/></svg>
            @else
                <svg class="h-6 w-6 animate-spin" style="animation-duration:1.4s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 9 9" stroke-linecap="round"/></svg>
            @endif
        </span>

        <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink">{{ $title }}</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm text-ink-2">{{ $text }}</p>

        <div class="mt-6 rounded-2xl border border-black/10 bg-surface p-4 text-left text-sm">
            <div class="flex justify-between"><span class="text-ink-3">Commande</span><span class="font-mono text-ink">{{ $order->order_number }}</span></div>
            <div class="mt-1.5 flex justify-between"><span class="text-ink-3">Montant</span><span class="text-ink">{{ number_format((float) $order->total_amount, 0, ',', ' ') }} {{ $order->currency }}</span></div>
            <div class="mt-1.5 flex justify-between"><span class="text-ink-3">Statut</span><x-ui.status-pill :status="$order->payment_status" /></div>
        </div>

        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
            @if ($status === 'paid')
                <x-ui.button variant="primary" :href="url('/mon-compte')">Voir mes commandes</x-ui.button>
            @elseif ($status === 'failed')
                <x-ui.button variant="primary" :href="route('cart')">Réessayer le paiement</x-ui.button>
            @endif
            <x-ui.button variant="secondary" :href="url('/')">Retour à l'accueil</x-ui.button>
        </div>

        @if (app()->environment('local'))
            <div class="mt-8 rounded-xl border border-amber/30 bg-amber/[0.06] p-3 text-left text-xs text-amber">
                <p class="font-semibold">Outils de développement (local uniquement)</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if ($order->payment_status !== 'paid')
                        <form method="POST" action="{{ route('dev.confirm-payment', $order) }}">@csrf
                            <button class="rounded-lg bg-emerald-500/90 px-3 py-1.5 font-semibold text-black">Simuler le paiement</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('dev.refund', $order) }}">@csrf
                            <button class="rounded-lg bg-rust/90 px-3 py-1.5 font-semibold text-white">Simuler un remboursement</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@if ($status === 'pending' && ! $isCod)
    <script>setTimeout(() => location.reload(), 5000);</script>
@endif
@endsection
