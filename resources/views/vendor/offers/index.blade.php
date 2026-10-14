@extends('layouts.app')

@section('title', 'Mes offres')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $filters = ['' => 'Toutes', 'draft' => 'Brouillons', 'pending_review' => 'En validation', 'changes_requested' => 'À corriger', 'approved' => 'En ligne', 'rejected' => 'Refusées'];
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'];
    $current = (string) request('status', '');
    $tabs = collect($filters)->mapWithKeys(fn ($lbl, $k) => [
        route('vendor.offers.index', array_filter(['status' => $k])) => ['label' => $lbl, 'active' => $current === (string) $k],
    ])->all();
@endphp

<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes offres</h1>
        <p class="mt-2 text-[15px] text-ink-2">Chaque offre est relue par l'équipe avant sa mise en ligne.</p>
    </div>
    <a href="{{ route('vendor.offers.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nouvelle offre
    </a>
</header>

<x-ui.tabs :items="$tabs" class="mt-8 w-fit" aria-label="Filtrer par statut" />

@if ($offers->isEmpty())
    <x-ui.empty-state class="mt-6" icon="box" title="Aucune offre ici"
                      text="Créez une offre : elle sera relue par l'équipe avant sa mise en ligne.">
        <x-slot:cta>
            <a href="{{ route('vendor.offers.create') }}" class="inline-flex min-h-11 items-center rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">Créer une offre</a>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <ul class="mt-6 divide-y divide-line rounded-2xl border border-line bg-surface shadow-[0_1px_2px_rgba(28,25,23,0.04)]">
        @foreach ($offers as $offer)
            <li class="flex flex-wrap items-center gap-x-4 gap-y-3 p-4 sm:flex-nowrap sm:px-5">
                <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-ground-alt">
                    @if ($offer->main_image_path)
                        <img src="{{ Storage::url($offer->main_image_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full items-center justify-center text-ink-3">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10"/></svg>
                        </span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="line-clamp-2 font-medium text-ink sm:line-clamp-1">{{ $offer->title }}</p>
                    <p class="mt-0.5 text-[13px] text-ink-3">
                        {{ $typeLabel[$offer->type] ?? ucfirst($offer->type) }}
                        · <span class="font-semibold text-ink">{{ $money($offer->effectivePrice()) }} {{ $offer->currency }}</span>
                        · mis à jour {{ $offer->updated_at->diffForHumans() }}
                    </p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <x-ui.status-pill :status="$offer->status" />
                        @if ($offer->is_online)
                            <span class="text-[13px] font-medium text-sage">Visible en boutique</span>
                        @endif
                    </div>
                    @if ($offer->review_note && in_array($offer->status, ['changes_requested', 'rejected'], true))
                        <p class="mt-2 rounded-lg border border-amber/25 bg-amber-tint px-3 py-2 text-[13px] leading-snug text-amber">
                            <span class="font-semibold">Note de l'équipe :</span> {{ \Illuminate\Support\Str::limit($offer->review_note, 140) }}
                        </p>
                    @endif
                </div>

                <a href="{{ route('vendor.offers.edit', $offer) }}"
                   class="inline-flex min-h-11 shrink-0 items-center rounded-full border border-line-strong px-5 text-sm font-semibold text-ink transition-colors hover:border-ink">
                    Modifier<span class="sr-only"> l'offre {{ $offer->title }}</span>
                </a>
            </li>
        @endforeach
    </ul>
    <x-ui.pagination :paginator="$offers" class="mt-6" />
@endif
@endsection
