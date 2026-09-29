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

        {{-- Configuration de la commission spécifique à ce produit --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Commission produit</h2>
                @if ($offer->commission_rate !== null)
                    <span class="inline-flex items-center rounded-full bg-accent-soft px-2.5 py-0.5 text-xs font-semibold text-accent">
                        {{ rtrim(rtrim(number_format($offer->commission_rate * 100, 2), '0'), '.') }} % (spécifique)
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-black/5 px-2.5 py-0.5 text-xs text-ink-3">
                        Barème standard
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs text-ink-3">
                Pourcentage perçu par la plateforme sur ce produit spécifique. Prioritaire sur les règles vendeur et catégorie. Laissez vide pour appliquer le barème standard.
            </p>
            <form method="POST" action="{{ route('admin.marketplace.offers.commission', $offer) }}" class="mt-3 flex gap-2">
                @csrf
                @method('PUT')
                <div class="relative flex-1">
                    <input name="commission_rate_percent" type="number" step="0.01" min="0" max="90"
                           value="{{ $offer->commission_rate !== null ? number_format($offer->commission_rate * 100, 2, '.', '') : '' }}"
                           placeholder="Ex : 15"
                           class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 pr-8 text-sm text-ink focus:border-accent focus:ring-1 focus:ring-accent">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-ink-3">%</span>
                </div>
                <button type="submit" class="h-10 rounded-xl border border-black/10 bg-ground-alt px-4 text-sm font-semibold text-ink hover:bg-ground hover:border-accent/40 transition-colors">
                    Enregistrer
                </button>
            </form>
            @if ($offer->commission_rate !== null)
                <p class="mt-2 text-[11px] text-accent">
                    ✓ Surcharge active : <strong>{{ number_format($offer->commission_rate * 100, 2) }} %</strong> prélevés sur chaque vente de cette offre.
                </p>
            @endif
        </section>
    </div>
</div>

<p class="mt-6 text-sm"><a href="{{ route('admin.marketplace.offers.index') }}" class="text-accent hover:underline">← File de modération</a></p>
@endsection
