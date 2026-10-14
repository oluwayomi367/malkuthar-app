@props([
    'title' => 'Rien à afficher',
    'text' => null,
    'icon' => null,   // nom court : box | search | cart | image | wallet | inbox | star | calendar | download | users
])

@php
    $paths = [
        'box' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10',
        'search' => 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-4.3-4.3',
        'cart' => 'M3 4h2l1.6 9.2A1.5 1.5 0 0 0 8 14.4h8.8a1.5 1.5 0 0 0 1.47-1.2L19.5 7H6M8.5 19.5a1 1 0 1 0 0 .01M16.5 19.5a1 1 0 1 0 0 .01',
        'image' => 'M4 5h16v14H4zM4 15l4-4 4 4 4-6 4 6',
        'wallet' => 'M3 7h15a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h11M17 12h.01',
        'inbox' => 'M4 13h4l1 3h6l1-3h4M4 13l2-8h12l2 8v6H4z',
        'star' => 'M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 18.8 6.2 21.9l1.1-6.5L2.6 9.8l6.5-.9z',
        'calendar' => 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
        'download' => 'M12 4v11M7.5 10.5L12 15l4.5-4.5M5 19h14',
        'users' => 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20a6.5 6.5 0 0 1 13 0M16 4.2a3.5 3.5 0 0 1 0 6.6M18 14.3a6.5 6.5 0 0 1 3.5 5.7',
    ];
    $d = $paths[is_string($icon) ? $icon : ''] ?? $paths['box'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface px-6 py-12 text-center']) }}>
    <svg class="mx-auto h-8 w-8 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="{{ $d }}" />
    </svg>
    <p class="mt-4 text-base font-semibold text-ink">{{ $title }}</p>
    @if ($text)
        <p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-ink-2">{{ $text }}</p>
    @endif
    @isset($cta)
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $cta }}</div>
    @endisset
    {{ $slot }}
</div>
