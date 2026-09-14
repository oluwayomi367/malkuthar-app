@extends('layouts.app')

@section('title', 'Commande ' . $order->order_number)
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<a href="{{ route('account.orders') }}" class="inline-flex items-center gap-1.5 text-sm text-ink-2 hover:text-ink">← Mes commandes</a>

<div class="mt-3 flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink">
        Commande <span class="font-mono text-xl text-ink-2">{{ $order->order_number }}</span>
    </h1>
    <x-ui.status-pill :status="$order->payment_status" />
</div>
<p class="mt-1 text-sm text-ink-3">Passée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}</p>

{{-- Suivi de livraison --}}
@if ($order->has_physical_items && $order->payment_status === 'paid')
    <div class="mt-8 rounded-2xl border border-black/10 bg-surface p-5">
        <h2 class="mb-4 font-display text-sm font-semibold uppercase tracking-wide text-ink-2">Suivi de livraison</h2>
        <x-account.order-tracker :order="$order" />
    </div>
@endif

{{-- Lignes --}}
<div class="mt-8 overflow-hidden rounded-2xl border border-black/10">
    <table class="w-full text-sm">
        <thead class="bg-surface text-left text-xs uppercase tracking-wide text-ink-3">
            <tr>
                <th class="px-4 py-3 font-semibold">Article</th>
                <th class="px-4 py-3 text-center font-semibold">Qté</th>
                <th class="px-4 py-3 text-right font-semibold">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-black/[0.06]">
            @foreach ($order->items as $item)
                <tr>
                    <td class="px-4 py-3">
                        <span class="text-ink">{{ $item->product_name }}</span>
                        @if ($item->variant_sku)
                            <span class="block text-xs text-ink-3">{{ $item->variant_sku }}</span>
                        @endif
                        @if ($item->reservation_datetime)
                            <span class="block text-xs text-ink-3">RDV : {{ $item->reservation_datetime->translatedFormat('d/m/Y H:i') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-ink-2">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-right text-ink">{{ $m($item->total_price) }} {{ $item->currency }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t border-black/10 text-ink-2">
            <tr><td colspan="2" class="px-4 py-2 text-right">Sous-total</td><td class="px-4 py-2 text-right">{{ $m($order->subtotal) }} {{ $order->currency }}</td></tr>
            @if ((float) $order->discount_total > 0)
                <tr><td colspan="2" class="px-4 py-2 text-right">Remise @if ($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="px-4 py-2 text-right text-rust">−{{ $m($order->discount_total) }} {{ $order->currency }}</td></tr>
            @endif
            @if ((float) $order->shipping_fee > 0)
                <tr><td colspan="2" class="px-4 py-2 text-right">Livraison</td><td class="px-4 py-2 text-right">{{ $m($order->shipping_fee) }} {{ $order->currency }}</td></tr>
            @endif
            <tr class="text-ink"><td colspan="2" class="px-4 py-3 text-right font-semibold">Total</td><td class="px-4 py-3 text-right font-semibold">{{ $m($order->total_amount) }} {{ $order->currency }}</td></tr>
        </tfoot>
    </table>
</div>

{{-- Adresse de livraison --}}
@if ($order->shipping_address)
    @php $a = $order->shipping_address; @endphp
    <div class="mt-6 rounded-2xl border border-black/10 bg-surface p-5 text-sm text-ink-2">
        <h2 class="mb-2 font-display text-sm font-semibold uppercase tracking-wide text-ink-2">Adresse de livraison</h2>
        <p class="text-ink">{{ $a['name'] ?? ($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '') }}</p>
        <p>{{ $a['line1'] ?? $a['address'] ?? '' }}</p>
        @if (!empty($a['line2'])) <p>{{ $a['line2'] }}</p> @endif
        <p>{{ trim(($a['postal_code'] ?? '') . ' ' . ($a['city'] ?? '')) }}</p>
        <p>{{ $a['country'] ?? '' }}</p>
        @if (!empty($a['phone'])) <p class="mt-1">{{ $a['phone'] }}</p> @endif
    </div>
@endif
@endsection
