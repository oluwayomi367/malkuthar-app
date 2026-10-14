@props([
    'value',
    'label' => 'Copier',
])

<button
    type="button"
    x-data="{ copied: false }"
    x-on:click="navigator.clipboard.writeText(@js($value)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
    {{ $attributes->merge([
        'class' => 'inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-full border border-line-strong bg-surface '
            .'px-4 text-sm font-semibold text-ink transition-colors hover:border-ink',
    ]) }}
>
    <svg x-show="!copied" class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5V4A1.5 1.5 0 0 0 9 2.5H4A1.5 1.5 0 0 0 2.5 4v5A1.5 1.5 0 0 0 4 10.5h1.5"/>
    </svg>
    <svg x-show="copied" x-cloak class="h-4 w-4 text-sage" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M3.5 8.5l3 3 6-7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span x-text="copied ? 'Copié' : @js($label)" aria-live="polite">{{ $label }}</span>
</button>
