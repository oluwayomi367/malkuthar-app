@props([
    'space' => 'affiliate',
])

@php
    use App\Support\SpaceNavigation;

    $links = SpaceNavigation::links($space);
    $spaceTitle = SpaceNavigation::title($space);

    // Classes littérales (lues par Tailwind) — teinte de repère par espace.
    $activeBg = [
        'account' => 'bg-space-client-soft',
        'affiliate' => 'bg-space-affiliate-soft',
        'vendor' => 'bg-space-vendor-soft',
    ][$space] ?? 'bg-space-client-soft';
    $activeIcon = [
        'account' => 'text-space-client',
        'affiliate' => 'text-space-affiliate',
        'vendor' => 'text-space-vendor',
    ][$space] ?? 'text-space-client';
    $eyebrow = [
        'account' => 'text-ink-2',
        'affiliate' => 'text-accent',
        'vendor' => 'text-space-vendor',
    ][$space] ?? 'text-ink-2';
@endphp

{{-- Menu latéral (bureau) --}}
<aside class="hidden w-60 shrink-0 lg:block">
    <div class="sticky top-24">
        <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $eyebrow }}">{{ $spaceTitle }}</p>
        <nav class="flex flex-col gap-0.5 text-sm" aria-label="{{ $spaceTitle }}">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @class([
                    'flex min-h-11 items-center gap-3 rounded-xl px-3 transition-colors',
                    $activeBg.' font-semibold text-ink' => $link['active'],
                    'text-ink-2 hover:bg-surface hover:text-ink' => ! $link['active'],
                ]) @if ($link['active']) aria-current="page" @endif>
                    <svg @class(['h-[18px] w-[18px] shrink-0', $activeIcon => $link['active'], 'text-ink-3' => ! $link['active']])
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        @isset($slot)
            @if (trim($slot) !== '')
                <div class="mt-6">{{ $slot }}</div>
            @endif
        @endisset
    </div>
</aside>

{{-- Mobile : les rubriques absentes de la barre d'onglets --}}
@php $secondary = array_filter($links, fn ($l) => ! $l['mobile']); @endphp
@if (count($secondary) > 0)
    <nav class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:hidden" aria-label="Autres rubriques — {{ $spaceTitle }}">
        @foreach ($secondary as $link)
            <a href="{{ $link['href'] }}" @class([
                'inline-flex h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-3.5 text-[13px]',
                'border-ink bg-ink font-semibold text-white' => $link['active'],
                'border-line bg-surface text-ink-2' => ! $link['active'],
            ]) @if ($link['active']) aria-current="page" @endif>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $link['icon'] }}"/>
                </svg>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
