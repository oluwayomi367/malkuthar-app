@extends('layouts.app')

@section('title', 'Commande ' . $suborder->suborder_number)
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'];

    // Étapes de préparation, dans l'ordre.
    $steps = ['unfulfilled' => 'À traiter', 'processing' => 'En préparation', 'shipped' => 'Expédiée', 'delivered' => 'Livrée', 'completed' => 'Terminée'];
    $order = array_keys($steps);
    $currentIndex = array_search($suborder->fulfilment_status, $order, true);
    $isCancelled = $currentIndex === false;
    $canAct = $suborder->order->payment_status === 'paid' && ! in_array($suborder->fulfilment_status, ['completed', 'cancelled'], true);
    $input = 'min-h-11 w-full rounded-xl border border-line-strong bg-surface px-3.5 py-2 text-[15px] text-ink placeholder:text-ink-3 transition-colors hover:border-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<a href="{{ route('vendor.orders.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
    Mes commandes
</a>

<header class="mt-2">
    <h1 class="break-all font-mono text-[26px] font-semibold leading-tight text-ink sm:text-[30px]">{{ $suborder->suborder_number }}</h1>
    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[15px] text-ink-2">
        <span>Commande {{ $suborder->order->order_number }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ optional($suborder->order->paid_at)->translatedFormat('d F Y') ?? 'paiement en attente' }}</span>
        <x-ui.status-pill :status="$suborder->fulfilment_status" />
    </p>
</header>

{{-- Étapes --}}
@unless ($isCancelled)
    <ol class="mt-8 grid grid-cols-5 gap-2" aria-label="Avancement de la commande">
        @foreach ($steps as $key => $label)
            @php
                $i = $loop->index;
                $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'todo');
            @endphp
            <li class="min-w-0" @if ($state === 'current') aria-current="step" @endif>
                <span @class([
                    'block h-1.5 rounded-full',
                    'bg-space-vendor' => $state !== 'todo',
                    'bg-line' => $state === 'todo',
                ]) aria-hidden="true"></span>
                <span @class([
                    'mt-2 block truncate text-[12px] sm:text-[13px]',
                    'font-semibold text-ink' => $state === 'current',
                    'text-ink-2' => $state === 'done',
                    'text-ink-3' => $state === 'todo',
                ])>{{ $label }}<span class="sr-only">{{ $state === 'done' ? ' (fait)' : ($state === 'current' ? ' (étape actuelle)' : '') }}</span></span>
            </li>
        @endforeach
    </ol>
@endunless

<div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:items-start">
    {{-- Articles et montants --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="items-title">
        <h2 id="items-title" class="font-sans text-lg font-semibold text-ink">Articles</h2>
        <ul class="mt-3 divide-y divide-line border-t border-line">
            @foreach ($suborder->items as $item)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3.5 text-sm">
                    <div class="min-w-0">
                        <p class="text-ink"><span class="font-semibold">{{ $item->quantity }} ×</span> {{ $item->title }}</p>
                        <p class="mt-0.5 text-[13px] text-ink-3">{{ $typeLabel[$item->type] ?? ucfirst($item->type) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="whitespace-nowrap font-medium text-ink">{{ $money($item->line_total) }}</span>
                        @if ($item->type === 'service' && $item->fulfilment_status === 'unfulfilled')
                            <form method="POST" action="{{ route('vendor.orders.confirm-booking', [$suborder, $item]) }}">@csrf
                                <button class="inline-flex min-h-11 items-center rounded-full border border-line-strong px-4 text-sm font-semibold text-ink transition-colors hover:border-ink">Confirmer le RDV</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <dl class="mt-2 space-y-2 border-t border-line pt-4 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-ink-2">Brut</dt><dd class="text-ink">{{ $money($suborder->vendor_gross) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-ink-2">Commission</dt><dd class="text-ink">− {{ $money($suborder->commission_amount) }}</dd></div>
            <div class="flex items-baseline justify-between gap-4 border-t border-line pt-3">
                <dt class="font-semibold text-ink">Net</dt>
                <dd class="font-display text-[28px] font-semibold leading-none text-ink">{{ $money($suborder->vendor_net) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 pt-1"><dt class="text-ink-2">Règlement</dt><dd><x-ui.status-pill :status="$suborder->settlement_status" /></dd></div>
        </dl>
        @if ($suborder->available_at)
            <p class="mt-3 text-[13px] text-ink-3">Disponible au retrait à partir du {{ $suborder->available_at->format('d/m/Y') }}.</p>
        @endif
    </section>

    <div class="space-y-6">
        {{-- Traitement --}}
        @if ($canAct)
            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="process-title">
                <h2 id="process-title" class="font-sans text-lg font-semibold text-ink">Traitement</h2>
                <p class="mt-1 text-sm text-ink-2">Renseignez le suivi d'expédition, puis confirmez la livraison.</p>

                <form method="POST" action="{{ route('vendor.orders.ship', $suborder) }}" class="mt-4 grid gap-4">@csrf
                    <div class="flex flex-col gap-1.5">
                        <label for="carrier" class="text-sm font-semibold text-ink">Transporteur</label>
                        <input id="carrier" name="carrier" placeholder="Transporteur" value="{{ $suborder->shipping_carrier }}" class="{{ $input }}">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="tracking" class="text-sm font-semibold text-ink">N° de suivi</label>
                        <input id="tracking" name="tracking" placeholder="N° de suivi" value="{{ $suborder->tracking_number }}" class="{{ $input }}">
                    </div>
                    <div>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">Marquer expédié</button>
                    </div>
                </form>

                <div class="mt-5 border-t border-line pt-4">
                    <p class="text-sm font-semibold text-ink">Passer à l'étape</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['delivered' => 'Livré', 'completed' => 'Terminé'] as $to => $lbl)
                            <form method="POST" action="{{ route('vendor.orders.advance', $suborder) }}">@csrf
                                <input type="hidden" name="to" value="{{ $to }}">
                                <button class="inline-flex min-h-11 items-center rounded-full border border-line-strong px-5 text-sm font-semibold text-ink transition-colors hover:border-ink">{{ $lbl }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Avis --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="reviews-title">
            <h2 id="reviews-title" class="font-sans text-lg font-semibold text-ink">Avis récents</h2>
            @forelse ($reviews as $r)
                <div class="mt-4 border-t border-line pt-4 text-sm">
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="inline-flex text-accent" aria-label="{{ $r->rating }} sur 5">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="{{ $s <= $r->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 18.8 6.2 21.9l1.1-6.5L2.6 9.8l6.5-.9z"/></svg>
                            @endfor
                        </span>
                        @if ($r->title)<span class="font-medium text-ink">{{ $r->title }}</span>@endif
                    </p>
                    @if ($r->body)<p class="mt-1.5 leading-relaxed text-ink-2">{{ $r->body }}</p>@endif
                    @if ($r->vendor_reply)
                        <p class="mt-2 rounded-xl bg-ground-alt px-3 py-2 text-[13px] leading-relaxed text-ink-2"><span class="font-semibold text-ink">Votre réponse :</span> {{ $r->vendor_reply }}</p>
                    @else
                        <form method="POST" action="{{ route('vendor.reviews.reply', $r) }}" class="mt-3 flex flex-wrap gap-2">@csrf
                            <label for="reply-{{ $r->id }}" class="sr-only">Répondre à cet avis</label>
                            <input id="reply-{{ $r->id }}" name="reply" required placeholder="Répondre…" class="{{ $input }} min-w-0 flex-1">
                            <button class="inline-flex min-h-11 items-center rounded-full border border-line-strong px-5 text-sm font-semibold text-ink transition-colors hover:border-ink">Envoyer</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="mt-2 text-sm text-ink-2">Aucun avis pour ces articles.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
