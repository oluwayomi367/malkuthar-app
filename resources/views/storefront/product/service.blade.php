@extends('layouts.storefront')

@php $price = $product->promotional_price ?? $product->price; @endphp

@section('title', $product->name)
@section('seo_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))

@section('content')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Service',
    'name' => $product->name, 'description' => strip_tags((string) $product->description),
    'offers' => ['@type' => 'Offer', 'price' => (float) $price, 'priceCurrency' => $product->currency],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<div x-data="{
        rdvDate: '', rdvTime: '',
        get ready() { return this.rdvDate && this.rdvTime; },
        add() {
            if (! this.ready) return;
            $store.cart.add({
                product_id: '{{ $product->id }}', name: @js($product->name), type: 'service',
                price: {{ (float) $price }}, currency: '{{ $product->currency }}', quantity: 1,
                reservation_date: this.rdvDate, reservation_time: this.rdvTime,
            });
            window.location = '{{ route('cart') }}';
        }
    }"
    x-on:slot-picked="rdvDate = $event.detail.date; rdvTime = $event.detail.time">

    <div class="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
        <div class="lg:sticky lg:top-20 lg:self-start">
            @include('storefront.product.partials.breadcrumb')
            <x-commerce.product-gallery :product="$product" class="mt-3" />
        </div>

        <div class="mt-6 lg:mt-0">
            <h1 class="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{{ $product->name }}</h1>

            <div class="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <x-commerce.price :price="$price" :compare-at="$product->promotional_price ? $product->price : null" :currency="$product->currency" size="lg" />
                @if ($product->duration_minutes)
                    <span class="text-sm text-ink-3">· {{ $product->duration_minutes }} min</span>
                @endif
            </div>

            <p class="mt-2 inline-flex items-center gap-2 text-sm text-ink-2">
                <svg class="h-4 w-4 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8l2.5 1.5" stroke-linecap="round"/></svg>
                @if ($product->delivery_mode === 'visioconférence' || $product->meeting_link)
                    Visioconférence — lien communiqué après réservation
                @elseif ($product->location)
                    {{ $product->location }}
                @else
                    Modalités communiquées après réservation
                @endif
            </p>

            <div class="mt-5">
                <x-commerce.booking-calendar :product="$product" />
            </div>

            <div class="mt-6 hidden lg:block">
                <x-ui.button variant="primary" :full="true" x-on:click="add()" x-bind:disabled="!ready"
                    x-text="ready ? 'Réserver ce créneau' : 'Choisissez un créneau'"></x-ui.button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
        @include('storefront.product.partials.description', ['accordions' => [
            'Déroulé de la séance' => 'La séance débute à l\'heure réservée. Prévoyez un environnement calme et une connexion stable pour les rendez-vous en visio.',
            'Annulation / report' => 'Report ou annulation possible depuis votre espace client. Un rappel vous est envoyé avant le rendez-vous.',
        ]])

        <x-commerce.reassurance variant="service" class="mt-10 border-t border-line pt-8" />
    </div>

    <x-layout.sticky-cta>
        <x-slot:info>
            <span class="font-semibold text-ink">{{ number_format((float) $price, 0, ',', ' ') }} {{ $product->currency }}</span>
        </x-slot:info>
        <x-ui.button variant="primary" :full="true" x-on:click="add()" x-bind:disabled="!ready"
            x-text="ready ? 'Réserver' : 'Choisir un créneau'"></x-ui.button>
    </x-layout.sticky-cta>
</div>
@endsection
