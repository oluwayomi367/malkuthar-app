@extends('layouts.admin')

@section('title', 'Administration — Modération des offres')
@section('eyebrow', 'Marketplace')
@section('heading', 'Modération des offres')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<nav class="mb-5 flex flex-wrap gap-2 text-sm">
    @foreach (['pending_review' => 'À valider', 'changes_requested' => 'À corriger', 'approved' => 'Approuvées', 'rejected' => 'Refusées', 'suspended' => 'Suspendues', 'all' => 'Toutes'] as $k => $lbl)
        <a href="{{ route('admin.marketplace.offers.index', ['status' => $k]) }}"
           @class([
               'rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => $status === $k,
               'border-black/10 text-ink-2 hover:text-ink' => $status !== $k,
           ])>
            {{ $lbl }}@if (isset($counts[$k])) <span class="text-ink-3">({{ $counts[$k] }})</span>@endif
        </a>
    @endforeach
</nav>

@if ($offers->isEmpty())
    <x-ui.empty-state title="Rien à afficher" text="Aucune offre pour ce filtre." />
@else
    <div class="space-y-3">
        @foreach ($offers as $offer)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.marketplace.offers.show', $offer) }}" class="font-semibold text-ink hover:text-accent">{{ $offer->title }}</a>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $offer->vendor?->display_name }} · {{ $offer->category?->name }} · {{ ucfirst($offer->type) }}
                            · {{ $money($offer->effectivePrice()) }} {{ $offer->currency }}
                            @if ($offer->submitted_at) · soumis {{ $offer->submitted_at->diffForHumans() }}@endif
                        </p>
                    </div>
                    <x-ui.status-pill :status="$offer->status" />
                </div>

                @if ($offer->status === 'pending_review')
                    <div class="mt-3 flex flex-wrap items-start gap-2 border-t border-black/[0.06] pt-3">
                        <form method="POST" action="{{ route('admin.marketplace.offers.approve', $offer) }}">@csrf
                            <button class="h-9 rounded-lg border border-brand-green/40 bg-brand-green/10 px-3 text-xs font-semibold text-brand-green hover:bg-brand-green/20">Approuver</button>
                        </form>
                        <form method="POST" action="{{ route('admin.marketplace.offers.request-changes', $offer) }}" class="flex gap-1">@csrf
                            <input name="note" required placeholder="Corrections demandées" class="h-9 w-52 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <button class="h-9 rounded-lg border border-amber/40 bg-amber/10 px-3 text-xs font-semibold text-amber">Demander</button>
                        </form>
                        <form method="POST" action="{{ route('admin.marketplace.offers.reject', $offer) }}" class="flex gap-1">@csrf
                            <input name="reason" required placeholder="Motif de refus" class="h-9 w-52 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust">Refuser</button>
                        </form>
                        <a href="{{ route('admin.marketplace.offers.show', $offer) }}" class="h-9 rounded-lg border border-black/10 px-3 text-xs font-semibold leading-9 text-ink-2">Détail</a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$offers" class="mt-6" />
@endif
@endsection
