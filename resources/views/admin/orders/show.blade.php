@extends('layouts.admin')

@section('title', 'Commande ' . $order->order_number)
@section('eyebrow', 'Ventes')
@section('heading', 'Commande ' . $order->order_number)

@section('actions')
    <x-ui.button :href="route('admin.orders.index')" variant="secondary">← Retour</x-ui.button>
@endsection

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $providerLabels = ['fedapay' => 'FedaPay', 'cinetpay' => 'CinetPay', 'cod' => 'Paiement à la livraison'];
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
    $statusMeta = [
        'pending' => ['En attente', 'muted'], 'processing' => ['En cours', 'accent'],
        'completed' => ['Terminée', 'accent'], 'cancelled' => ['Annulée', 'rust'], 'refunded' => ['Remboursée', 'rust'],
    ];
    [$sLabel, $sTone] = $statusMeta[$order->status] ?? [$order->status, 'muted'];
    $fulfilmentLabels = ['preparing' => 'En préparation', 'shipped' => 'Expédiée', 'in_delivery' => 'En livraison', 'delivered' => 'Livrée'];
    $isCodPending = $order->isCashOnDelivery() && $order->payment_status === 'pending' && $order->status !== 'cancelled';
@endphp

<div class="mb-6 flex flex-wrap items-center gap-2">
    <x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge>
    <x-ui.status-pill :status="$order->payment_status" />
    <span class="text-sm text-ink-3">{{ $providerLabels[$order->payment_provider] ?? ($order->payment_provider ?: '—') }}</span>
    <span class="ml-auto text-sm text-ink-3">Passée le {{ $order->created_at?->format('d/m/Y à H:i') }}</span>
</div>

<div class="grid gap-8 lg:grid-cols-[1fr_320px] lg:gap-10">
    {{-- Colonne principale --}}
    <div class="space-y-6">
        {{-- Lignes --}}
        <div class="overflow-x-auto rounded-2xl border border-black/10">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Article</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 text-center font-semibold">Qté</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/[0.06]">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($item->product)
                                    <a href="{{ route('admin.products.show', $item->product) }}" class="font-medium text-ink hover:text-accent">{{ $item->product_name }}</a>
                                @else
                                    <span class="font-medium text-ink">{{ $item->product_name }}</span>
                                @endif
                                @if ($item->variant_sku)<span class="block text-xs text-ink-3">{{ $item->variant_sku }}</span>@endif
                                @if ($item->reservation_datetime)<span class="block text-xs text-ink-3">RDV {{ $item->reservation_datetime->format('d/m/Y H:i') }}</span>@endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge tone="muted">{{ $typeLabels[$item->product_type] ?? $item->product_type }}</x-ui.badge>
                                @if ($item->is_affiliable_snapshot)<x-ui.badge tone="accent" class="ml-1">Affiliable</x-ui.badge>@endif
                            </td>
                            <td class="px-4 py-3 text-center text-ink-2">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right text-ink">{{ $m($item->total_price) }} {{ $item->currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-black/10 text-sm">
                    <tr><td colspan="3" class="px-4 py-1.5 text-right text-ink-3">Sous-total</td><td class="px-4 py-1.5 text-right text-ink-2">{{ $m($order->subtotal) }} {{ $order->currency }}</td></tr>
                    @if ((float) $order->discount_total > 0)
                        <tr><td colspan="3" class="px-4 py-1.5 text-right text-ink-3">Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="px-4 py-1.5 text-right text-ink-2">−{{ $m($order->discount_total) }}</td></tr>
                    @endif
                    @if ((float) $order->shipping_fee > 0)
                        <tr><td colspan="3" class="px-4 py-1.5 text-right text-ink-3">Livraison</td><td class="px-4 py-1.5 text-right text-ink-2">{{ $m($order->shipping_fee) }}</td></tr>
                    @endif
                    <tr class="font-semibold"><td colspan="3" class="px-4 py-2 text-right text-ink">Total</td><td class="px-4 py-2 text-right text-ink">{{ $m($order->total_amount) }} {{ $order->currency }}</td></tr>
                </tfoot>
            </table>
        </div>

        {{-- Commissions générées --}}
        <div class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Commissions d'affiliation</p>
            @if ($order->referringAffiliate)
                <p class="mt-2 text-sm text-ink-2">
                    Vente référée par
                    <a href="{{ route('admin.affiliates.show', $order->referringAffiliate) }}" class="text-accent hover:underline">{{ trim("{$order->referringAffiliate->first_name} {$order->referringAffiliate->last_name}") }}</a>
                    <span class="text-ink-3">({{ $order->referringAffiliate->affiliate_id }})</span>
                </p>
            @else
                <p class="mt-2 text-sm text-ink-3">Aucun affilié référent sur cette commande.</p>
            @endif

            @if ($commissions->isNotEmpty())
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($commissions as $c)
                        <li class="flex items-baseline justify-between gap-3 border-b border-black/[0.05] pb-2 last:border-0 last:pb-0">
                            <span class="text-ink-2">
                                Niveau {{ $c->level }} — {{ $c->user?->first_name }} {{ $c->user?->last_name }}
                                @if ($c->source_type === 'commission_reversal')<span class="text-rust"> · reprise</span>@endif
                            </span>
                            <span class="flex items-center gap-2 shrink-0">
                                <span class="{{ $c->source_type === 'commission_reversal' ? 'text-rust' : 'text-ink' }}">{{ $c->source_type === 'commission_reversal' ? '−' : '' }}{{ $m($c->amount) }} {{ $c->currency }}</span>
                                <x-ui.status-pill :status="$c->status" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            @elseif ($order->payment_status === 'paid')
                <p class="mt-3 text-sm text-ink-3">Aucune commission (aucune ligne digitale affiliable, ou pas d'affilié référent).</p>
            @else
                <p class="mt-3 text-sm text-ink-3">Les commissions seront calculées une fois le paiement confirmé.</p>
            @endif
        </div>

        {{-- Client & livraison --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-black/10 bg-surface p-5 text-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Client</p>
                @if ($order->user)
                    <p class="mt-2 text-ink">{{ trim("{$order->user->first_name} {$order->user->last_name}") ?: '—' }}</p>
                    <p class="text-ink-3">{{ $order->user->email }}</p>
                    @if ($order->user->phone)<p class="text-ink-3">{{ $order->user->phone }}</p>@endif
                @else
                    <p class="mt-2 text-ink-3">Commande invité</p>
                @endif
            </div>
            <div class="rounded-2xl border border-black/10 bg-surface p-5 text-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Livraison</p>
                @if ($order->shipping_address)
                    @php $a = $order->shipping_address; @endphp
                    <p class="mt-2 text-ink">{{ data_get($a, 'first_name') }} {{ data_get($a, 'last_name') }}</p>
                    <p class="text-ink-3">{{ data_get($a, 'address') }}</p>
                    <p class="text-ink-3">{{ data_get($a, 'neighborhood') }} {{ data_get($a, 'city') }} {{ data_get($a, 'country') }}</p>
                    <p class="text-ink-3">{{ data_get($a, 'phone') }}</p>
                @else
                    <p class="mt-2 text-ink-3">Pas d'adresse (commande sans article physique).</p>
                @endif
                @if ($order->shipping_status)
                    <p class="mt-2"><x-ui.badge tone="accent">{{ $fulfilmentLabels[$order->shipping_status] ?? $order->shipping_status }}</x-ui.badge></p>
                @endif
                @if ($order->tracking_number)<p class="mt-1 text-ink-3">Suivi : {{ $order->shipping_carrier }} {{ $order->tracking_number }}</p>@endif
            </div>
        </div>

        @if ($order->notes)
            <div class="rounded-2xl border border-black/10 bg-surface p-5 text-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Notes</p>
                <p class="mt-2 whitespace-pre-line text-ink-2">{{ $order->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Colonne latérale : paiement + actions --}}
    <div class="space-y-4">
        <div class="rounded-2xl border border-black/10 bg-surface p-5 text-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Paiement</p>
            <dl class="mt-2 space-y-1.5">
                <div class="flex justify-between"><dt class="text-ink-3">Mode</dt><dd class="text-ink">{{ $providerLabels[$order->payment_provider] ?? ($order->payment_provider ?: '—') }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-3">Méthode</dt><dd class="text-ink">{{ $order->payment_method ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-3">Référence</dt><dd class="font-mono text-xs text-ink">{{ $order->payment_reference ?: '—' }}</dd></div>
                @if ($order->cod_collected_at)
                    <div class="flex justify-between"><dt class="text-ink-3">Encaissé le</dt><dd class="text-ink">{{ $order->cod_collected_at->format('d/m/Y H:i') }}</dd></div>
                    @if ($order->codCollector)
                        <div class="flex justify-between"><dt class="text-ink-3">Par</dt><dd class="text-ink">{{ $order->codCollector->first_name }} {{ $order->codCollector->last_name }}</dd></div>
                    @endif
                @endif
            </dl>
        </div>

        {{-- Encaisser COD --}}
        @if ($isCodPending)
            <form method="POST" action="{{ route('admin.orders.collect-cod', $order) }}"
                  onsubmit="return confirm('Confirmer l\'encaissement de {{ $m($order->total_amount) }} {{ $order->currency }} ? Les commissions et la livraison seront déclenchées.')"
                  class="rounded-2xl border border-brand-green/30 bg-brand-green/[0.06] p-5">
                @csrf
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-green">Paiement à la livraison</p>
                <p class="mt-1 text-sm text-ink-2">Déclarez l'encaissement une fois l'argent reçu du client.</p>
                <button class="mt-3 h-10 w-full rounded-xl bg-brand-green text-sm font-semibold text-white hover:opacity-90">Encaisser {{ $m($order->total_amount) }} {{ $order->currency }}</button>
            </form>
        @endif

        {{-- Suivi de livraison --}}
        @if ($order->payment_status === 'paid' && $order->has_physical_items)
            <form method="POST" action="{{ route('admin.orders.fulfilment', $order) }}" class="rounded-2xl border border-black/10 bg-surface p-5">
                @csrf
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Suivi de livraison</p>
                <div class="mt-3 space-y-2">
                    <x-ui.select name="shipping_status" :selected="$order->shipping_status"
                        :options="['preparing' => 'En préparation', 'shipped' => 'Expédiée', 'in_delivery' => 'En livraison', 'delivered' => 'Livrée']" required />
                    <input name="shipping_carrier" value="{{ $order->shipping_carrier }}" placeholder="Transporteur"
                           class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
                    <input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="N° de suivi"
                           class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
                    <button class="h-10 w-full rounded-xl border border-black/15 bg-ground/60 text-sm font-semibold text-ink hover:bg-ground">Mettre à jour</button>
                </div>
            </form>
        @endif

        {{-- Rembourser --}}
        @if ($order->payment_status === 'paid')
            <form method="POST" action="{{ route('admin.orders.refund', $order) }}"
                  onsubmit="return confirm('Rembourser cette commande ? Les commissions liées seront reprises et le stock réapprovisionné.')"
                  class="rounded-2xl border border-rust/30 bg-rust/[0.05] p-5">
                @csrf
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-rust">Rembourser</p>
                <input name="reason" placeholder="Motif (facultatif)"
                       class="mt-3 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
                <button class="mt-2 h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust hover:bg-rust-tint">Rembourser la commande</button>
            </form>
        {{-- Annuler (non payée) --}}
        @elseif ($order->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}"
                  onsubmit="return confirm('Annuler cette commande ? Le stock réservé et les créneaux seront libérés.')"
                  class="rounded-2xl border border-rust/30 bg-rust/[0.05] p-5">
                @csrf
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-rust">Annuler</p>
                <input name="reason" placeholder="Motif (facultatif)"
                       class="mt-3 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
                <button class="mt-2 h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust hover:bg-rust-tint">Annuler la commande</button>
            </form>
        @endif
    </div>
</div>
@endsection
