@props([
    'available' => null,
    'lowThreshold' => 5,
])

@php
    $available = $available === null ? null : (int) $available;
@endphp

@if ($available === null)
    {{-- pas de suivi de stock --}}
@elseif ($available <= 0)
    <p {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-rust']) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Épuisé
    </p>
@elseif ($available <= $lowThreshold)
    <p {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-rust']) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Plus que {{ $available }} en stock
    </p>
@else
    <p {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-ink-3']) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-forest"></span>En stock
    </p>
@endif
