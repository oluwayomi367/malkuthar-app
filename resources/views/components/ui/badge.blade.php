@props([
    'tone' => 'ink',   // ink | gold | rust | muted | accent
])

@php
    $tones = [
        'ink' => 'border-black/15 bg-black/[0.04] text-ink',
        'accent' => 'border-accent/30 bg-accent/10 text-accent',
        'gold' => 'border-gold/40 bg-gold/10 text-gold',
        'rust' => 'border-rust/30 bg-rust/10 text-rust',
        'muted' => 'border-black/10 text-ink-3',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 '
        .'text-[10.5px] font-semibold uppercase tracking-[0.07em] backdrop-blur '
        .($tones[$tone] ?? $tones['ink']),
]) }}>{{ $slot }}</span>
