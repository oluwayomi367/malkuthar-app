@props([
    'variant' => 'primary',   // primary | secondary | ghost
    'href' => null,
    'type' => 'button',
    'full' => false,
])

@php
    $base = 'group relative inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold '
        .'transition-[transform,box-shadow,background-color,opacity] duration-200 '
        .'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent '
        .'disabled:pointer-events-none disabled:opacity-40 h-11 px-5 sm:h-10';

    $variants = [
        'primary' => 'text-white bg-accent shadow-[0_1px_2px_rgba(46,44,40,0.12)] hover:bg-[#77602a] active:bg-[#6a5624]',
        'secondary' => 'text-ink bg-surface border border-line-strong hover:bg-ground-alt hover:border-ink-3',
        'ghost' => 'text-accent px-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($full ? 'w-full' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
