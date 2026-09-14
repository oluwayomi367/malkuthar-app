@props([
    'price',
    'compareAt' => null,      // prix barré (avant promo)
    'currency' => 'FCFA',
    'size' => 'md',           // sm | md | lg
])

@php
    $sizes = [
        'sm' => 'text-sm',
        'md' => 'text-[15px]',
        'lg' => 'text-xl',
    ];
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $onSale = $compareAt !== null && (float) $compareAt > (float) $price;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2 font-semibold text-ink '.($sizes[$size] ?? $sizes['md'])]) }}>
    <span>{{ $fmt($price) }}<span class="ml-1 text-[0.75em] font-medium text-ink-3">{{ $currency }}</span></span>
    @if ($onSale)
        <span class="text-[0.8em] font-normal text-ink-3 line-through">{{ $fmt($compareAt) }}</span>
    @endif
</span>
