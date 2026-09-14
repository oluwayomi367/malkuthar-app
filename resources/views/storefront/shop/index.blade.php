@extends('layouts.storefront')

@php
    $typeTitles = ['digital' => 'Produits digitaux', 'physical' => 'Produits physiques', 'service' => 'Services'];
    $typeIntro = [
        'digital' => 'Ebooks, formations, audios — livraison immédiate après paiement.',
        'physical' => 'Livres, coffrets, accessoires — expédition suivie.',
        'service' => 'Consultations et séances, réservées sur créneau.',
    ];
    $heading = $typeTitles[$type] ?? 'La boutique';
@endphp

@section('title', $heading)
@section('seo_description', "Parcourez le catalogue MALKUTHAR : {$heading}.")

@section('content')
<div class="relative overflow-hidden">
    <div class="relative mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-12">

        <header class="mb-8 border-b border-black/[0.06] pb-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Catalogue</p>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-[-0.01em] text-ink sm:text-4xl">{{ $heading }}</h1>
            <p class="mt-2 text-sm text-ink-2">
                {{ $typeIntro[$type] ?? 'Digital, physique et service — tout MALKUTHAR au même endroit.' }}
                <span class="text-ink-3">· {{ $products->total() }} {{ \Illuminate\Support\Str::plural('produit', $products->total()) }}</span>
            </p>
        </header>

        @include('storefront.shop.partials.filter-bar')

        <div class="mt-8">
            @include('storefront.shop.partials.grid')
        </div>

    </div>
</div>
@endsection
