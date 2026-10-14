@extends('layouts.app')

@section('title', 'Commande ' . $order->order_number)
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<a href="{{ route('account.orders') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
    Mes commandes
</a>

<header class="mt-2 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink">
            Commande <span class="font-mono text-xl font-normal text-ink-2">{{ $order->order_number }}</span>
        </h1>
        <p class="mt-1 text-sm text-ink-2">Passée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}</p>
    </div>
    <x-ui.status-pill :status="$order->payment_status" class="mt-2" />
</header>

{{-- Suivi de livraison --}}
@if ($order->has_physical_items && $order->payment_status === 'paid')
    <section class="mt-8 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="tracking-title">
        <h2 id="tracking-title" class="mb-4 font-sans text-lg font-semibold text-ink">Suivi de livraison</h2>
        <x-account.order-tracker :order="$order" />
    </section>
@endif

{{-- Lignes --}}
<section class="mt-6 rounded-2xl border border-line bg-surface" aria-labelledby="items-title">
    <h2 id="items-title" class="px-5 pt-5 font-sans text-lg font-semibold text-ink sm:px-6">Articles</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full min-w-[420px] text-sm">
            <thead class="border-y border-line text-left text-[13px] text-ink-3">
                <tr>
                    <th scope="col" class="px-5 py-3 font-semibold sm:px-6">Article</th>
                    <th scope="col" class="px-5 py-3 text-center font-semibold">Qté</th>
                    <th scope="col" class="px-5 py-3 text-right font-semibold sm:px-6">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-5 py-3.5 sm:px-6">
                            <span class="font-medium text-ink">{{ $item->product_name }}</span>
                            @if ($item->variant_sku)
                                <span class="mt-0.5 block text-[13px] text-ink-3">{{ $item->variant_sku }}</span>
                            @endif
                            @if ($item->reservation_datetime)
                                <span class="mt-0.5 block text-[13px] text-ink-3">Rendez-vous : {{ $item->reservation_datetime->translatedFormat('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center text-ink-2">{{ $item->quantity }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-ink sm:px-6">{{ $m($item->total_price) }} {{ $item->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-line text-ink-2">
                <tr><td colspan="2" class="px-5 pb-1 pt-4 text-right">Sous-total</td><td class="whitespace-nowrap px-5 pb-1 pt-4 text-right sm:px-6">{{ $m($order->subtotal) }} {{ $order->currency }}</td></tr>
                @if ((float) $order->discount_total > 0)
                    <tr><td colspan="2" class="px-5 py-1 text-right">Remise @if ($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="whitespace-nowrap px-5 py-1 text-right text-rust sm:px-6">−{{ $m($order->discount_total) }} {{ $order->currency }}</td></tr>
                @endif
                @if ((float) $order->shipping_fee > 0)
                    <tr><td colspan="2" class="px-5 py-1 text-right">Livraison</td><td class="whitespace-nowrap px-5 py-1 text-right sm:px-6">{{ $m($order->shipping_fee) }} {{ $order->currency }}</td></tr>
                @endif
                <tr class="text-ink"><td colspan="2" class="px-5 pb-5 pt-2 text-right font-semibold">Total</td><td class="whitespace-nowrap px-5 pb-5 pt-2 text-right text-base font-semibold sm:px-6">{{ $m($order->total_amount) }} {{ $order->currency }}</td></tr>
            </tfoot>
        </table>
    </div>
</section>

{{-- Adresse de livraison --}}
@if ($order->shipping_address)
    @php $a = $order->shipping_address; @endphp
    <section class="mt-6 rounded-2xl border border-line bg-surface p-5 text-sm leading-relaxed text-ink-2 sm:p-6" aria-labelledby="address-title">
        <h2 id="address-title" class="mb-2 font-sans text-lg font-semibold text-ink">Adresse de livraison</h2>
        <p class="font-medium text-ink">{{ $a['name'] ?? ($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '') }}</p>
        <p>{{ $a['line1'] ?? $a['address'] ?? '' }}</p>
        @if (!empty($a['line2'])) <p>{{ $a['line2'] }}</p> @endif
        <p>{{ trim(($a['postal_code'] ?? '') . ' ' . ($a['city'] ?? '')) }}</p>
        <p>{{ $a['country'] ?? '' }}</p>
        @if (!empty($a['phone'])) <p class="mt-1">{{ $a['phone'] }}</p> @endif
    </section>
@endif
@endsection
