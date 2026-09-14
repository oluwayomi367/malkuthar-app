@extends('layouts.storefront')

@section('title', $vendor->display_name . ' — Place de marché')
@section('seo_description', \Illuminate\Support\Str::limit(strip_tags($vendor->bio), 155))

@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp

@if ($vendor->banner_path)
    <div class="h-40 w-full overflow-hidden bg-ground-alt sm:h-52">
        <img src="{{ Storage::url($vendor->banner_path) }}" alt="" class="h-full w-full object-cover">
    </div>
@endif

<div class="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-14">
    <nav class="text-xs text-ink-3">
        <a href="{{ route('marketplace.shop') }}" class="hover:text-ink">Place de marché</a> / <span>{{ $vendor->display_name }}</span>
    </nav>

    <header class="mt-4 flex flex-wrap items-start gap-4 {{ $vendor->banner_path ? '-mt-16' : '' }}">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_2px_10px_-4px_rgba(46,44,40,0.2)]">
            @if ($vendor->logo_path)
                <img src="{{ Storage::url($vendor->logo_path) }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="font-display text-2xl font-bold text-ink-3">{{ \Illuminate\Support\Str::of($vendor->display_name)->substr(0, 1)->upper() }}</span>
            @endif
        </div>
        <div class="{{ $vendor->banner_path ? 'pt-16' : '' }}">
            <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{{ $vendor->display_name }}</h1>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-ink-3">
                @if ($vendor->region)<span>{{ $vendor->region }}@if ($vendor->city) · {{ $vendor->city }}@endif</span>@endif
                <span>· {{ $offers->total() }} offre{{ $offers->total() > 1 ? 's' : '' }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-green/10 px-2 py-0.5 text-[11px] font-semibold text-brand-green">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>Vendeur vérifié
                </span>
            </p>
        </div>
    </header>

    @if ($vendor->bio)<p class="mt-4 max-w-2xl text-ink-2">{{ $vendor->bio }}</p>@endif

    @if ($offers->isEmpty())
        <x-ui.empty-state class="mt-8" title="Aucune offre en ligne" text="Ce vendeur n'a pas d'offre publiée pour le moment." />
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($offers as $offer)
                <x-marketplace.offer-card :offer="$offer" />
            @endforeach
        </div>
        <x-ui.pagination :paginator="$offers" class="mt-8" />
    @endif
</div>
@endsection
