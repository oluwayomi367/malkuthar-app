@extends('layouts.app')

@section('title', 'Mes offres')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $filters = ['' => 'Toutes', 'draft' => 'Brouillons', 'pending_review' => 'En validation', 'changes_requested' => 'À corriger', 'approved' => 'En ligne', 'rejected' => 'Refusées'];
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes offres</h1>
    </div>
    <x-ui.button variant="primary" :href="route('vendor.offers.create')">+ Nouvelle offre</x-ui.button>
</div>

<nav class="mt-5 flex flex-wrap gap-2 text-sm">
    @foreach ($filters as $k => $lbl)
        <a href="{{ route('vendor.offers.index', array_filter(['status' => $k])) }}"
           @class(['rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => request('status', '') === $k,
               'border-line text-ink-2 hover:text-ink' => request('status', '') !== $k])>{{ $lbl }}</a>
    @endforeach
</nav>

@if ($offers->isEmpty())
    <div class="mt-8 rounded-2xl border border-dashed border-line-strong bg-surface p-10 text-center">
        <svg class="mx-auto h-10 w-10 text-line-strong" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
        <p class="mt-3 font-display text-base font-semibold text-ink">Aucune offre ici</p>
        <p class="mt-1 text-sm text-ink-2">Créez une offre : elle sera relue par l'équipe avant sa mise en ligne.</p>
        <div class="mt-4"><x-ui.button variant="primary" :href="route('vendor.offers.create')">Créer une offre</x-ui.button></div>
    </div>
@else
    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        @foreach ($offers as $offer)
            <a href="{{ route('vendor.offers.edit', $offer) }}"
               class="group flex gap-4 rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong">
                <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-ground-alt">
                    @if ($offer->main_image_path)
                        <img src="{{ Storage::url($offer->main_image_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full items-center justify-center text-line-strong">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                        </span>
                    @endif
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-start justify-between gap-2">
                        <span class="line-clamp-1 font-medium text-ink group-hover:text-accent">{{ $offer->title }}</span>
                        <x-ui.status-pill :status="$offer->status" />
                    </span>
                    <span class="mt-1 block text-xs text-ink-3">
                        {{ ucfirst($offer->type) }} · {{ $money($offer->effectivePrice()) }} {{ $offer->currency }}
                        @if ($offer->is_online) · <span class="text-brand-green">en ligne</span>@endif
                        · maj {{ $offer->updated_at->diffForHumans() }}
                    </span>
                    @if ($offer->review_note && in_array($offer->status, ['changes_requested', 'rejected'], true))
                        <span class="mt-1.5 block rounded bg-amber/10 px-2 py-1 text-[11px] text-amber">{{ \Illuminate\Support\Str::limit($offer->review_note, 90) }}</span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$offers" class="mt-6" />
@endif
@endsection
