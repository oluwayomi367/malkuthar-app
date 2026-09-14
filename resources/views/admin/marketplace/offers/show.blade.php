@extends('layouts.admin')

@section('title', 'Modération — '.$offer->title)
@section('eyebrow', 'Marketplace · Modération')
@section('heading', $offer->title)

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $candidate = $pendingRevision?->payload;
@endphp

<div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
    <div class="space-y-6">
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Contenu {{ $candidate ? 'soumis' : 'actuel' }}</h2>
                <x-ui.status-pill :status="$offer->status" />
            </div>
            @php $c = $candidate ?? [
                'title' => $offer->title, 'summary' => $offer->summary, 'description' => $offer->description,
                'price' => $offer->price, 'sale_price' => $offer->sale_price, 'custom_fields' => $offer->custom_fields,
                'detail' => optional($offer->detail())->toArray() ?? [],
            ]; @endphp
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-ink-3">Titre</dt><dd class="text-ink">{{ $c['title'] }}</dd></div>
                <div><dt class="text-ink-3">Accroche</dt><dd class="text-ink">{{ $c['summary'] ?: '—' }}</dd></div>
                <div><dt class="text-ink-3">Prix</dt><dd class="text-ink">{{ $money($c['price']) }} {{ $offer->currency }}@if (! empty($c['sale_price'])) (promo {{ $money($c['sale_price']) }})@endif</dd></div>
                <div><dt class="text-ink-3">Vendeur</dt><dd class="text-ink">{{ $offer->vendor->display_name }} — {{ $offer->vendor->user?->email }}</dd></div>
                <div><dt class="text-ink-3">Catégorie · type</dt><dd class="text-ink">{{ $offer->category?->name }} · {{ ucfirst($offer->type) }}</dd></div>
            </dl>
            <div class="mt-3 border-t border-black/[0.06] pt-3">
                <p class="text-xs text-ink-3">Description</p>
                <p class="mt-1 whitespace-pre-line text-sm text-ink-2">{{ $c['description'] }}</p>
            </div>
            @if (! empty($c['custom_fields']))
                <div class="mt-3 border-t border-black/[0.06] pt-3">
                    <p class="text-xs text-ink-3">Caractéristiques</p>
                    <ul class="mt-1 text-sm text-ink-2">
                        @foreach ((array) $c['custom_fields'] as $k => $v)
                            <li><span class="text-ink-3">{{ $k }}:</span> {{ is_array($v) ? implode(', ', $v) : $v }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($candidate && $offer->first_published_at)
                <p class="mt-3 rounded-lg bg-amber/10 px-3 py-2 text-xs text-amber">
                    Une version est déjà en ligne. L'approbation remplacera son contenu par celui-ci.
                </p>
            @endif
        </section>

        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Historique de modération</h2>
            @if ($offer->reviews->isEmpty())
                <p class="mt-2 text-sm text-ink-3">Aucune décision encore.</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($offer->reviews as $r)
                        <li class="flex items-start justify-between gap-3 border-b border-black/[0.04] pb-2">
                            <span>
                                <span class="font-medium text-ink">{{ ['approved' => 'Approuvée', 'rejected' => 'Refusée', 'changes_requested' => 'Corrections'][$r->decision] ?? $r->decision }}</span>
                                @if ($r->note)<span class="text-ink-2"> — {{ $r->note }}</span>@endif
                            </span>
                            <span class="shrink-0 text-xs text-ink-3">v{{ $r->offer_version }} · {{ $r->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="space-y-3">
        @if ($offer->status === 'pending_review')
            <section class="rounded-2xl border border-black/10 bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Décision</h2>
                <form method="POST" action="{{ route('admin.marketplace.offers.approve', $offer) }}" class="mt-3">@csrf
                    <input name="note" placeholder="Note (facultatif)" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    <button class="mt-2 h-10 w-full rounded-xl border border-brand-green/40 bg-brand-green/10 text-sm font-semibold text-brand-green">Approuver</button>
                </form>
                <form method="POST" action="{{ route('admin.marketplace.offers.request-changes', $offer) }}" class="mt-3">@csrf
                    <input name="note" required placeholder="Corrections à apporter" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    <button class="mt-2 h-10 w-full rounded-xl border border-amber/40 bg-amber/10 text-sm font-semibold text-amber">Demander des corrections</button>
                </form>
                <form method="POST" action="{{ route('admin.marketplace.offers.reject', $offer) }}" class="mt-3">@csrf
                    <input name="reason" required placeholder="Motif de refus" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    <button class="mt-2 h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust">Refuser</button>
                </form>
            </section>
        @elseif ($offer->status === 'approved')
            <form method="POST" action="{{ route('admin.marketplace.offers.suspend', $offer) }}" class="rounded-2xl border border-black/10 bg-surface p-5">@csrf
                <h2 class="font-display text-base font-semibold text-ink">Suspendre</h2>
                <input name="reason" required placeholder="Motif" class="mt-3 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                <button class="mt-2 h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust">Suspendre l'offre</button>
            </form>
        @elseif ($offer->status === 'suspended')
            <form method="POST" action="{{ route('admin.marketplace.offers.unsuspend', $offer) }}" class="rounded-2xl border border-black/10 bg-surface p-5">@csrf
                <button class="h-10 w-full rounded-xl border border-brand-green/40 bg-brand-green/10 text-sm font-semibold text-brand-green">Rétablir l'offre</button>
            </form>
        @endif
    </div>
</div>

<p class="mt-6 text-sm"><a href="{{ route('admin.marketplace.offers.index') }}" class="text-accent hover:underline">← File de modération</a></p>
@endsection
