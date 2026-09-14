@props([
    'label',
    'value',
    'currency' => null,
    'emphasis' => false,
])

<div {{ $attributes->merge([
    'class' => 'relative overflow-hidden rounded-2xl border p-5 '
        .($emphasis
            ? 'border-accent/25 bg-accent-soft/50 shadow-[0_1px_2px_rgba(46,44,40,0.04)]'
            : 'border-line bg-surface shadow-[0_1px_2px_rgba(46,44,40,0.04),0_10px_30px_-26px_rgba(46,44,40,0.16)]'),
]) }}>
    <p class="relative text-[10.5px] font-semibold uppercase tracking-[0.09em] text-ink-3">{{ $label }}</p>
    <p class="relative mt-1 font-display font-semibold tracking-[-0.01em] text-ink {{ $emphasis ? 'text-[30px] leading-none' : 'text-xl' }}">
        {{ $value }}@if ($currency)<span class="ml-1 text-sm font-medium text-ink-3">{{ $currency }}</span>@endif
    </p>
    @isset($foot)
        <div class="relative mt-2 text-xs text-ink-2">{{ $foot }}</div>
    @endisset
    @isset($action)
        <div class="relative mt-3">{{ $action }}</div>
    @endisset
</div>
