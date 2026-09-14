@props([
    'tone' => 'info',   // info | success | warning | error
])

@php
    $tones = [
        'info'    => 'border-line bg-ground-alt text-ink-2',
        'success' => 'border-brand-green/30 bg-brand-green/[0.08] text-brand-green',
        'warning' => 'border-amber/40 bg-amber/[0.10] text-amber',
        'error'   => 'border-rust/30 bg-rust/[0.08] text-rust',
    ];
@endphp

<div {{ $attributes->merge([
    'class' => 'rounded-xl border px-4 py-3 text-sm backdrop-blur '.($tones[$tone] ?? $tones['info']),
    'role' => 'status',
]) }}>{{ $slot }}</div>
