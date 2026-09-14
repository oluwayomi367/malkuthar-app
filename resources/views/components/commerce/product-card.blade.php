@props(['product'])

@php
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
    $isAffiliable = $product->type === 'digital' && $product->is_affiliable;
    $price = $product->promotional_price ?? $product->price;
@endphp

<a href="{{ route('catalog.show', $product->slug) }}"
   {{ $attributes->merge(['class' => 'group flex flex-col']) }}>
    <div class="relative overflow-hidden rounded-2xl border border-black/10 bg-surface">
        <div class="aspect-[4/5]">
            @if ($product->main_image)
                <img src="{{ $product->main_image }}" alt="{{ $product->name }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-black/[0.04] to-transparent text-ink-3">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1">
                        <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="9" r="1.5"/>
                    </svg>
                </div>
            @endif
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
        </div>

        <span class="absolute left-2.5 top-2.5">
            <x-ui.badge tone="ink">{{ $typeLabels[$product->type] ?? $product->type }}</x-ui.badge>
        </span>
        @if ($isAffiliable)
            <span class="absolute right-2.5 top-2.5">
                <x-ui.badge tone="gold">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="currentColor"><path d="M6 0l1.6 3.4L11 4l-2.6 2.3.7 3.7L6 8.2 2.9 10l.7-3.7L1 4l3.4-.6z"/></svg>
                    Affiliable
                </x-ui.badge>
            </span>
        @endif
    </div>

    <p class="mt-2.5 line-clamp-2 text-[15px] font-medium leading-snug text-ink transition-colors group-hover:text-accent">{{ $product->name }}</p>
    @if ($product->category)
        <p class="mt-0.5 text-xs text-ink-3">{{ $product->category }}</p>
    @endif
    <div class="mt-1">
        <x-commerce.price :price="$price" :compare-at="$product->promotional_price ? $product->price : null" :currency="$product->currency" />
    </div>
</a>
