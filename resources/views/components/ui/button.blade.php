@props([
    'variant' => 'primary',   // primary | secondary | ghost
    'href' => null,
    'type' => 'button',
    'full' => false,
])

@php
    $base = 'group relative inline-flex items-center justify-center gap-2 rounded-full text-sm font-semibold '
        .'transition-[transform,box-shadow,background-color,opacity] duration-200 '
        .'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent '
        .'disabled:pointer-events-none disabled:opacity-40 min-h-11 px-6';

    $variants = [
        'primary' => 'text-white bg-accent hover:bg-accent-2 active:bg-accent-2',
        'secondary' => 'text-ink bg-surface border border-line-strong hover:border-ink hover:bg-surface',
        'ghost' => 'text-accent px-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($full ? 'w-full' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
