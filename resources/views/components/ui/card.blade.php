@props([
    'href' => null,
    'padded' => true,
    'hoverable' => false,
])

@php
    $isInteractive = ! empty($href) || $hoverable;
    $classes = 'block rounded-2xl border border-line bg-surface '
        .'shadow-[0_1px_2px_rgba(28,25,23,0.04)] '
        .($padded ? 'p-5 sm:p-6 ' : '')
        .($isInteractive ? 'transition-all duration-300 hover:border-line-strong hover:shadow-[0_16px_32px_-20px_rgba(28,25,23,0.18)] ' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
@endif

