@props([
    'items' => [],
    'active' => null,
])

<nav {{ $attributes->merge(['class' => 'flex gap-1 overflow-x-auto rounded-xl border border-black/10 bg-surface p-1 backdrop-blur']) }}>
    @foreach ($items as $url => $item)
        @php
            $itemUrl = is_array($item) ? ($item['url'] ?? $url) : $url;
            $itemLabel = is_array($item) ? ($item['label'] ?? $item['url'] ?? $url) : $item;
            $isActive = is_array($item)
                ? ($item['active'] ?? false)
                : ($active !== null && (string) $active === (string) $itemUrl);
        @endphp
        <a href="{{ $itemUrl }}"
           @class([
               'shrink-0 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
               'bg-accent text-white shadow-[0_6px_20px_-8px] shadow-black/10' => $isActive,
               'text-ink-3 hover:text-ink' => ! $isActive,
           ])>{{ $itemLabel }}</a>
    @endforeach
</nav>
