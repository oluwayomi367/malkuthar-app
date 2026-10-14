@props([
    'items' => [],
    'active' => null,
])

<nav {{ $attributes->merge(['class' => 'flex max-w-full gap-1 overflow-x-auto rounded-full border border-line bg-ground-alt p-1']) }}>
    @foreach ($items as $url => $item)
        @php
            $itemUrl = is_array($item) ? ($item['url'] ?? $url) : $url;
            $itemLabel = is_array($item) ? ($item['label'] ?? $item['url'] ?? $url) : $item;
            $isActive = is_array($item)
                ? ($item['active'] ?? false)
                : ($active !== null && (string) $active === (string) $itemUrl);
        @endphp
        <a href="{{ $itemUrl }}"
           @if ($isActive) aria-current="page" @endif
           @class([
               'inline-flex min-h-10 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-sm font-semibold transition-colors',
               'bg-surface text-ink shadow-[0_1px_2px_rgba(28,25,23,0.08)]' => $isActive,
               'text-ink-2 hover:text-ink' => ! $isActive,
           ])>{{ $itemLabel }}</a>
    @endforeach
</nav>
