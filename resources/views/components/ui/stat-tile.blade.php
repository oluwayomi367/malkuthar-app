@props([
    'label',
    'value',
    'currency' => null,
    'emphasis' => false,
])

<div {{ $attributes->merge([
    'class' => 'relative overflow-hidden rounded-2xl border p-5 '
        .($emphasis
            ? 'border-ink bg-ink text-white'
            : 'border-line bg-surface'),
]) }}>
    <p class="relative text-xs font-semibold uppercase tracking-[0.1em] {{ $emphasis ? 'text-accent-light' : 'text-ink-3' }}">{{ $label }}</p>
    <p class="relative mt-2 font-display font-semibold leading-none {{ $emphasis ? 'text-[40px] text-white' : 'text-[32px] text-ink' }}">
        {{ $value }}@if ($currency)<span class="ml-1 font-sans text-sm font-medium {{ $emphasis ? 'text-white/70' : 'text-ink-3' }}">{{ $currency }}</span>@endif
    </p>
    @isset($foot)
        <div class="relative mt-2 text-xs {{ $emphasis ? 'text-white/75' : 'text-ink-2' }}">{{ $foot }}</div>
    @endisset
    @isset($action)
        <div class="relative mt-3">{{ $action }}</div>
    @endisset
</div>
