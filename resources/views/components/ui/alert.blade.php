@props([
    'tone' => 'info',   // info | success | warning | error
])

@php
    $tones = [
        'info'    => 'border-line bg-surface text-ink-2',
        'success' => 'border-sage/30 bg-sage-soft text-sage',
        'warning' => 'border-amber/30 bg-amber-tint text-amber',
        'error'   => 'border-rust/30 bg-rust-tint text-rust',
    ];
    $icons = [
        'info'    => 'M12 8h.01M11 12h1v5h1M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z',
        'success' => 'M8 12.5l2.5 2.5L16 9.5M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z',
        'warning' => 'M12 9v4M12 16.5h.01M10.3 4.2L2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0z',
        'error'   => 'M12 8v5M12 16h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z',
    ];
    $tone = isset($tones[$tone]) ? $tone : 'info';
@endphp

<div {{ $attributes->merge([
    'class' => 'flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-relaxed '.$tones[$tone],
    'role' => in_array($tone, ['error', 'warning'], true) ? 'alert' : 'status',
]) }}>
    <svg class="mt-0.5 h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="{{ $icons[$tone] }}"/>
    </svg>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
