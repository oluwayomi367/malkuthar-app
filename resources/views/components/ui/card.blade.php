@props([
    'href' => null,
    'padded' => true,
])

@php
    $classes = 'block rounded-2xl border border-line bg-surface '
        .'shadow-[0_1px_2px_rgba(46,44,40,0.04),0_10px_30px_-24px_rgba(46,44,40,0.16)] '
        .($padded ? 'p-5 sm:p-6 ' : '')
        .($href ? 'transition-[box-shadow,border-color] hover:border-line-strong hover:shadow-[0_2px_4px_rgba(46,44,40,0.05),0_16px_36px_-24px_rgba(46,44,40,0.22)]' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
@endif
