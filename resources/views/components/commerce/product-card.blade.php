@props(['product'])

@php
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
    $isAffiliable = $product->type === 'digital' && $product->is_affiliable;
    $price = $product->promotional_price ?? $product->price;
    $url = route('catalog.show', $product->slug);
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl border border-line bg-surface p-3 shadow-[0_1px_2px_rgba(28,25,23,0.04)]']) }}>
    <a href="{{ $url }}" class="block overflow-hidden rounded-xl bg-ground-alt" tabindex="-1" aria-hidden="true">
        <div class="aspect-[4/5]">
            @if ($product->main_image)
                <img src="{{ $product->main_image }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center text-ink-3">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="9" r="1.5"/>
                    </svg>
                </div>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col px-1 pb-1 pt-3">
        <div class="flex flex-wrap items-center gap-1.5">
            <x-ui.badge tone="muted">{{ $product->category ?: 'MALKUTHAR' }}</x-ui.badge>
            <x-ui.badge tone="ink">{{ $typeLabels[$product->type] ?? $product->type }}</x-ui.badge>
            @if ($isAffiliable)
                <x-ui.badge tone="gold">Affiliable</x-ui.badge>
            @endif
        </div>

        <h3 class="mt-2.5 text-base font-semibold leading-snug text-ink">
            <a href="{{ $url }}" class="line-clamp-2 hover:text-accent-2">{{ $product->name }}</a>
        </h3>

        <div class="mt-2 pb-3">
            <x-commerce.price :price="$price" :compare-at="$product->promotional_price ? $product->price : null" :currency="$product->currency" />
        </div>

        <a href="{{ $url }}"
           class="mt-auto inline-flex min-h-11 w-full items-center justify-center rounded-full border border-line-strong px-4 text-sm font-semibold text-ink transition-colors hover:border-ink hover:bg-deep hover:text-white">
            Voir le produit<span class="sr-only"> : {{ $product->name }}</span>
        </a>
    </div>
</article>
