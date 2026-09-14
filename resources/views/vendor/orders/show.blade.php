@extends('layouts.app')

@section('title', 'Commande ' . $suborder->suborder_number)
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
<h1 class="mt-1 font-display text-xl font-bold text-ink">{{ $suborder->suborder_number }}</h1>
<p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-ink-3">
    Commande {{ $suborder->order->order_number }} ·
    {{ optional($suborder->order->paid_at)->translatedFormat('d F Y') ?? 'paiement en attente' }}
    <x-ui.status-pill :status="$suborder->fulfilment_status" />
</p>

<div class="mt-5 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
    <div class="rounded-2xl border border-line bg-surface p-5">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-line">
                @foreach ($suborder->items as $item)
                    <tr>
                        <td class="py-2 text-ink">
                            {{ $item->quantity }} × {{ $item->title }}
                            <span class="block text-xs text-ink-3">{{ ucfirst($item->type) }}</span>
                        </td>
                        <td class="py-2 text-right text-ink-2">{{ $money($item->line_total) }}</td>
                        <td class="py-2 pl-3 text-right">
                            @if ($item->type === 'service' && $item->fulfilment_status === 'unfulfilled')
                                <form method="POST" action="{{ route('vendor.orders.confirm-booking', [$suborder, $item]) }}">@csrf
                                    <button class="text-xs font-semibold text-accent hover:underline">Confirmer le RDV</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 border-t border-line pt-3 text-sm">
            <div><dt class="text-ink-3">Brut</dt><dd class="text-ink">{{ $money($suborder->vendor_gross) }}</dd></div>
            <div><dt class="text-ink-3">Commission</dt><dd class="text-ink">− {{ $money($suborder->commission_amount) }}</dd></div>
            <div><dt class="text-ink-3">Net</dt><dd class="font-semibold text-ink">{{ $money($suborder->vendor_net) }}</dd></div>
            <div><dt class="text-ink-3">Règlement</dt><dd><x-ui.status-pill :status="$suborder->settlement_status" /></dd></div>
        </dl>
        @if ($suborder->available_at)
            <p class="mt-2 text-xs text-ink-3">Disponible au retrait à partir du {{ $suborder->available_at->format('d/m/Y') }}.</p>
        @endif
    </div>

    <div class="space-y-3">
        @if ($suborder->order->payment_status === 'paid' && ! in_array($suborder->fulfilment_status, ['completed', 'cancelled'], true))
            <section class="rounded-2xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Traitement</h2>
                <form method="POST" action="{{ route('vendor.orders.ship', $suborder) }}" class="mt-3 grid gap-2">@csrf
                    <input name="carrier" placeholder="Transporteur" value="{{ $suborder->shipping_carrier }}" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
                    <input name="tracking" placeholder="N° de suivi" value="{{ $suborder->tracking_number }}" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
                    <x-ui.button type="submit" variant="secondary">Marquer expédié</x-ui.button>
                </form>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (['delivered' => 'Livré', 'completed' => 'Terminé'] as $to => $lbl)
                        <form method="POST" action="{{ route('vendor.orders.advance', $suborder) }}">@csrf
                            <input type="hidden" name="to" value="{{ $to }}">
                            <button class="h-9 rounded-lg border border-line px-3 text-xs font-semibold text-ink-2 hover:text-ink">{{ $lbl }}</button>
                        </form>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Avis récents</h2>
            @forelse ($reviews as $r)
                <div class="mt-3 border-t border-line pt-3 text-sm first:mt-0 first:border-0 first:pt-0">
                    <p class="text-ink">{{ str_repeat('★', $r->rating) }}<span class="text-line-strong">{{ str_repeat('★', 5 - $r->rating) }}</span> <span class="text-ink-3">— {{ $r->title }}</span></p>
                    @if ($r->body)<p class="mt-1 text-ink-2">{{ $r->body }}</p>@endif
                    @if ($r->vendor_reply)
                        <p class="mt-1 rounded bg-ground-alt/60 px-2 py-1 text-xs text-ink-2">Votre réponse : {{ $r->vendor_reply }}</p>
                    @else
                        <form method="POST" action="{{ route('vendor.reviews.reply', $r) }}" class="mt-1 flex gap-1">@csrf
                            <input name="reply" required placeholder="Répondre…" class="h-8 flex-1 rounded border border-line bg-surface px-2 text-xs">
                            <button class="h-8 rounded border border-line px-2 text-xs font-semibold text-ink-2">Envoyer</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="mt-2 text-sm text-ink-3">Aucun avis pour ces articles.</p>
            @endforelse
        </section>
    </div>
</div>

<p class="mt-5 text-sm"><a href="{{ route('vendor.orders.index') }}" class="text-accent hover:underline">← Mes commandes</a></p>
@endsection
