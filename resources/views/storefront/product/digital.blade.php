@extends('layouts.storefront')

@php $price = $product->promotional_price ?? $product->price; @endphp

@section('title', $product->name)
@section('seo_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))

@section('content')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $product->name, 'description' => strip_tags((string) $product->description),
    'category' => $product->category,
    'offers' => ['@type' => 'Offer', 'price' => (float) $price, 'priceCurrency' => $product->currency,
        'availability' => $product->is_available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<div x-data="{
        add() {
            $store.cart.add({
                product_id: '{{ $product->id }}', name: @js($product->name), type: 'digital',
                price: {{ (float) $price }}, currency: '{{ $product->currency }}', quantity: 1,
            });
            window.location = '{{ route('cart') }}';
        }
    }">

    <div class="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
        <div class="lg:sticky lg:top-20 lg:self-start">
            @include('storefront.product.partials.breadcrumb')
            <x-commerce.product-gallery :product="$product" class="mt-3" />
        </div>

        <div class="mt-6 lg:mt-0">
            <h1 class="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{{ $product->name }}</h1>
            @if ($product->category)
                <p class="mt-1 text-sm text-ink-3">{{ $product->category }}@if ($product->sub_category) · {{ $product->sub_category }}@endif</p>
            @endif

            <div class="mt-3">
                <x-commerce.price :price="$price" :compare-at="$product->promotional_price ? $product->price : null" :currency="$product->currency" size="lg" />
            </div>

            <p class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-forest">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 1L2 9h5l-1 6 7-8H8z" stroke-linejoin="round"/></svg>
                Livraison immédiate par email après confirmation du paiement
            </p>

            <div class="mt-5">
                <x-commerce.affiliate-eligibility :product="$product" />
            </div>

            <div class="mt-6 hidden lg:block">
                <x-ui.button variant="primary" :full="true" x-on:click="add()">Acheter — {{ number_format((float) $price, 0, ',', ' ') }} {{ $product->currency }}</x-ui.button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
        @include('storefront.product.partials.description', ['accordions' => [
            'Format &amp; compatibilité' => 'Fichier téléchargeable, lisible sur ordinateur, tablette et smartphone.',
            'Livraison' => 'Lien de téléchargement sécurisé envoyé par email, valable 7 jours, dans la limite de plusieurs téléchargements.',
            'Remboursement' => 'Un produit digital consommé (téléchargé) n\'est pas remboursable, sauf défaut. Contactez le support en cas de problème.',
        ]])

        <x-commerce.reassurance variant="digital" class="mt-10 border-t border-line pt-8" />
    </div>

    <x-layout.sticky-cta>
        <x-slot:info>
            <span class="font-semibold text-ink">{{ number_format((float) $price, 0, ',', ' ') }} {{ $product->currency }}</span>
        </x-slot:info>
        <x-ui.button variant="primary" :full="true" x-on:click="add()">Acheter</x-ui.button>
    </x-layout.sticky-cta>
</div>
@endsection
