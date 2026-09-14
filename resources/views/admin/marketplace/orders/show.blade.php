@extends('layouts.admin')

@section('title', 'Commande ' . $order->order_number)
@section('eyebrow', 'Marketplace · Commande')
@section('heading', $order->order_number)

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
    <div class="space-y-4">
        @foreach ($order->suborders as $sub)
            <section class="rounded-2xl border border-black/10 bg-surface p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-ink">{{ $sub->vendor->display_name }}</h2>
                    <span class="text-xs text-ink-3">{{ $sub->suborder_number }}</span>
                </div>
                <table class="mt-3 w-full text-sm">
                    <tbody class="divide-y divide-black/[0.06]">
                        @foreach ($sub->items as $item)
                            <tr>
                                <td class="py-2 text-ink">{{ $item->quantity }} × {{ $item->title }}</td>
                                <td class="py-2 text-right text-ink-2">{{ $money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 border-t border-black/[0.06] pt-3 text-sm">
                    <div><dt class="text-ink-3">Brut vendeur</dt><dd class="text-ink">{{ $money($sub->vendor_gross) }}</dd></div>
                    <div><dt class="text-ink-3">Commission ({{ rtrim(rtrim(number_format($sub->commission_rate * 100, 2), '0'), '.') }} %)</dt><dd class="text-ink">− {{ $money($sub->commission_amount) }}</dd></div>
                    <div><dt class="text-ink-3">Net vendeur</dt><dd class="font-semibold text-ink">{{ $money($sub->vendor_net) }}</dd></div>
                    <div><dt class="text-ink-3">Règlement</dt><dd><x-ui.status-pill :status="$sub->settlement_status" /></dd></div>
                </dl>
                @if ($order->payment_status === 'paid' && $sub->settlement_status !== 'reversed')
                    <form method="POST" action="{{ route('admin.marketplace.suborders.refund', $sub) }}" class="mt-3 flex gap-1 border-t border-black/[0.06] pt-3">@csrf
                        <input name="reason" required placeholder="Motif du remboursement" class="h-9 flex-1 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                        <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust">Rembourser cette part</button>
                    </form>
                @endif
            </section>
        @endforeach
    </div>

    <div class="space-y-3">
        <section class="rounded-2xl border border-black/10 bg-surface p-5 text-sm">
            <h2 class="font-display text-base font-semibold text-ink">Commande</h2>
            <dl class="mt-3 space-y-1">
                <div><dt class="text-ink-3">Acheteur</dt><dd class="text-ink">{{ $order->buyer_email }}{{ $order->buyer_phone ? ' · '.$order->buyer_phone : '' }}</dd></div>
                <div><dt class="text-ink-3">Paiement</dt><dd class="text-ink">{{ $order->payment_provider }} — <x-ui.status-pill :status="$order->payment_status" /></dd></div>
                <div><dt class="text-ink-3">Total</dt><dd class="font-semibold text-ink">{{ $money($order->grand_total) }} {{ $order->currency }}</dd></div>
                @if ($order->paid_at)<div><dt class="text-ink-3">Payée le</dt><dd class="text-ink">{{ $order->paid_at->format('d/m/Y H:i') }}</dd></div>@endif
            </dl>
        </section>

        @if ($order->payment_status === 'pending')
            <form method="POST" action="{{ route('admin.marketplace.orders.mark-paid', $order) }}" class="rounded-2xl border border-black/10 bg-surface p-5">@csrf
                <button class="h-10 w-full rounded-xl border border-brand-green/40 bg-brand-green/10 text-sm font-semibold text-brand-green">Encaisser la commande</button>
            </form>
            <form method="POST" action="{{ route('admin.marketplace.orders.cancel', $order) }}" class="rounded-2xl border border-black/10 bg-surface p-5">@csrf
                <input name="reason" required placeholder="Motif d'annulation" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                <button class="mt-2 h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust">Annuler</button>
            </form>
        @endif
    </div>
</div>

<p class="mt-6 text-sm"><a href="{{ route('admin.marketplace.orders.index') }}" class="text-accent hover:underline">← Toutes les commandes</a></p>
@endsection
