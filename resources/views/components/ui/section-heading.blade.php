@props([
    'eyebrow' => null,
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'mb-4 flex items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)
            <p class="text-[10.5px] font-semibold uppercase tracking-[0.14em] text-accent">{{ $eyebrow }}</p>
        @endif
        @if ($title)
            <h2 class="mt-1.5 font-display text-lg font-semibold text-ink sm:text-xl">{{ $title }}</h2>
        @endif
        {{ $slot }}
    </div>
    @isset($action)
        <div class="shrink-0 text-sm text-ink-2">{{ $action }}</div>
    @endisset
</div>
