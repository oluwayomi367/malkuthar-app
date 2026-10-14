@props([
    'tone' => 'ink',   // ink | gold | rust | muted | accent
])

@php
    $tones = [
        'ink' => 'border-line-strong bg-ground-alt text-ink',
        'accent' => 'border-accent/25 bg-accent-soft text-accent-2',
        'gold' => 'border-accent/25 bg-accent-soft text-accent-2',
        'rust' => 'border-rust/30 bg-rust/10 text-rust',
        'muted' => 'border-line text-ink-2',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 '
        .'text-xs font-semibold '
        .($tones[$tone] ?? $tones['ink']),
]) }}>{{ $slot }}</span>
