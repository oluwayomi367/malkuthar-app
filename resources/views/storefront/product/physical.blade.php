@extends('layouts.storefront')

@php
    $price = $product->promotional_price ?? $product->price;
    $stock = $product->stock_available;

    // §12 — variantes actives, avec prix effectif / stock / image.
    $variants = $product->variants->where('is_active', true)->values();
    $hasVariants = $variants->isNotEmpty();

    $variantsJs = $variants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name,
        'sku' => $v->sku,
        'options' => (object) ($v->options ?? []),
        'price' => $v->effectivePrice(),
        'stock' => (int) $v->stock_available,
        'image' => $v->image ? asset(ltrim($v->image, '/')) : null,
    ]);

    // Groupes d'options pour le sélecteur : clé => [valeurs distinctes].
    $optionGroups = [];
    foreach ($variants as $v) {
        foreach ((array) $v->options as $k => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            $optionGroups[$k] ??= [];
            if (! in_array($val, $optionGroups[$k], true)) {
                $optionGroups[$k][] = $val;
            }
        }
    }
    $optionLabels = ['taille' => 'Taille', 'couleur' => 'Couleur', 'modele' => 'Modèle', 'format' => 'Format', 'autre' => 'Autre'];
@endphp

@section('title', $product->name)
@section('seo_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))

@section('content')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $product->name, 'description' => strip_tags((string) $product->description),
    'sku' => $product->sku, 'category' => $product->category,
    'offers' => ['@type' => 'Offer', 'price' => (float) $price, 'priceCurrency' => $product->currency,
        'availability' => ($stock === null || $stock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<div x-data="{
        qty: 1,
        basePrice: {{ (float) $price }},
        baseStock: {{ $stock === null ? 'null' : (int) $stock }},
        currency: '{{ $product->currency }}',
        hasVariants: {{ $hasVariants ? 'true' : 'false' }},
        variants: {{ \Illuminate\Support\Js::from($variantsJs) }},
        selection: {},
        get current() {
            if (!this.hasVariants) return null;
            return this.variants.find(v =>
                Object.keys(this.selection).length > 0 &&
                Object.entries(this.selection).every(([k, val]) => (v.options[k] ?? '') === val)
            ) ?? null;
        },
        get price() { return this.current ? this.current.price : this.basePrice; },
        get stock() { return this.current ? this.current.stock : this.baseStock; },
        get needsVariant() { return this.hasVariants && !this.current; },
        get outOfStock() { return this.stock !== null && this.stock <= 0; },
        get canAdd() { return !this.needsVariant && !this.outOfStock; },
        fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); },
        add() {
            if (!this.canAdd) return;
            $store.cart.add({
                product_id: '{{ $product->id }}',
                variant_id: this.current ? this.current.id : null,
                name: @js($product->name) + (this.current ? ' — ' + this.current.name : ''),
                type: 'physical',
                price: this.price, currency: this.currency,
                quantity: this.qty,
                variant_sku: this.current ? this.current.sku : null,
            });
            window.location = '{{ route('cart') }}';
        }
    }">

    <div class="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
        <div class="lg:sticky lg:top-20 lg:self-start">
            @include('storefront.product.partials.breadcrumb')
            <div class="relative mt-3">
                {{-- Image de la variante sélectionnée, superposée à la galerie --}}
                <template x-if="current && current.image">
                    <img :src="current.image" :alt="current.name" class="aspect-[4/5] w-full rounded-lg border border-line object-cover">
                </template>
                <div x-show="!(current && current.image)">
                    <x-commerce.product-gallery :product="$product" />
                </div>
            </div>
        </div>

        <div class="mt-6 lg:mt-0">
            <h1 class="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{{ $product->name }}</h1>
            @if ($product->sku)
                <p class="mt-1 font-mono text-xs text-ink-3">Réf. {{ $product->sku }}</p>
            @endif

            <div class="mt-3">
                <template x-if="current">
                    <p class="text-2xl font-semibold text-ink"><span x-text="fmt(price)"></span> <span class="text-base text-ink-2" x-text="currency"></span></p>
                </template>
                <div x-show="!current">
                    <x-commerce.price :price="$price" :compare-at="$product->promotional_price ? $product->price : null" :currency="$product->currency" size="lg" />
                </div>
            </div>

            @if ($hasVariants)
                <div class="mt-6 space-y-4">
                    @foreach ($optionGroups as $key => $values)
                        <div>
                            <p class="mb-1.5 text-[13px] font-semibold text-ink-2">{{ $optionLabels[$key] ?? ucfirst($key) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($values as $val)
                                    <button type="button"
                                            x-on:click="selection['{{ $key }}'] = (selection['{{ $key }}'] === @js($val) ? undefined : @js($val))"
                                            :class="selection['{{ $key }}'] === @js($val) ? 'border-ink bg-ink text-white' : 'border-line-strong text-ink hover:border-ink'"
                                            class="h-10 rounded-md border px-3 text-sm transition-colors">{{ $val }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p x-show="needsVariant" class="text-[13px] text-rust">Choisissez une variante pour continuer.</p>
                    <p x-show="current" x-cloak class="text-[13px] text-ink-2">
                        Sélection : <span class="font-semibold text-ink" x-text="current?.name"></span>
                        <span x-show="current && current.sku" class="font-mono text-ink-3"> · <span x-text="current?.sku"></span></span>
                    </p>
                </div>
            @endif

            <div class="mt-5">
                @if ($hasVariants)
                    <p class="text-[13px]" x-cloak
                       :class="stock !== null && stock <= 0 ? 'text-rust' : (stock !== null && stock <= 5 ? 'text-amber' : 'text-ink-2')"
                       x-text="current ? (stock <= 0 ? 'Variante épuisée' : (stock <= 5 ? 'Plus que ' + stock + ' en stock' : 'En stock')) : 'Sélectionnez une variante'"></p>
                @else
                    <x-commerce.stock-indicator :available="$stock" />
                @endif
            </div>

            <div class="mt-5 flex items-center gap-4">
                <span class="text-[13px] font-semibold text-ink-2">Quantité</span>
                <div class="inline-flex items-center rounded-md border border-line-strong/25">
                    <button type="button" x-on:click="qty = Math.max(1, qty - 1)" class="flex h-11 w-11 items-center justify-center text-ink" aria-label="Retirer">−</button>
                    <span class="w-8 text-center text-sm font-semibold" x-text="qty"></span>
                    <button type="button" x-on:click="qty = qty + 1" class="flex h-11 w-11 items-center justify-center text-ink" aria-label="Ajouter">+</button>
                </div>
            </div>

            <div class="mt-6 hidden lg:block">
                <x-ui.button variant="primary" :full="true" x-on:click="add()" x-bind:disabled="!canAdd"
                    x-text="needsVariant ? 'Choisir une variante' : (outOfStock ? 'Épuisé' : 'Ajouter au panier')"></x-ui.button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
        @include('storefront.product.partials.description', ['accordions' => [
            'Dimensions &amp; poids' => ($product->weight ? number_format((float) $product->weight, 2, ',', ' ').' kg. ' : '').'Détails complets sur la fiche d\'expédition.',
            'Livraison &amp; retours' => 'Expédition suivie sous 48 h à 7 jours selon la destination. Retour possible sous 14 jours pour un article non ouvert.',
        ]])

        <x-commerce.reassurance variant="physical" class="mt-10 border-t border-line pt-8" />
    </div>

    <x-layout.sticky-cta>
        <x-slot:info>
            <span class="font-semibold text-ink"><span x-text="fmt(price)"></span> {{ $product->currency }}</span>
        </x-slot:info>
        <x-ui.button variant="primary" :full="true" x-on:click="add()" x-bind:disabled="!canAdd"
            x-text="needsVariant ? 'Choisir une variante' : (outOfStock ? 'Épuisé' : 'Ajouter au panier')"></x-ui.button>
    </x-layout.sticky-cta>
</div>
@endsection
