@props(['product'])

@php
    $images = collect([$product->main_image])
        ->merge((array) ($product->gallery ?? []))
        ->filter()
        ->unique()
        ->values();
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
@endphp

<div x-data="{ current: {{ $images->isNotEmpty() ? 0 : 'null' }} }" {{ $attributes }}>
    <div class="relative overflow-hidden rounded-2xl border border-line bg-ground-alt">
        <div class="aspect-[4/5]">
            @forelse ($images as $i => $img)
                <img src="{{ $img }}" alt="{{ $product->name }}"
                     x-show="current === {{ $i }}"
                     class="h-full w-full object-cover">
            @empty
                <div class="flex h-full w-full items-center justify-center text-ink-3">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="9" r="1.5"/>
                    </svg>
                </div>
            @endforelse
        </div>
        <span class="absolute left-3 top-3">
            <x-ui.badge tone="ink" class="bg-surface">{{ $typeLabels[$product->type] ?? $product->type }}</x-ui.badge>
        </span>
    </div>

    @if ($images->count() > 1)
        <div class="mt-3 flex gap-2">
            @foreach ($images as $i => $img)
                <button type="button" x-on:click="current = {{ $i }}" aria-label="Afficher l'image {{ $i + 1 }}"
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2"
                        :class="current === {{ $i }} ? 'border-ink' : 'border-line'">
                    <img src="{{ $img }}" alt="" class="h-full w-full object-cover">
                </button>
            @endforeach
        </div>
    @endif

    @if ($product->presentation_video)
        <div class="mt-3 overflow-hidden rounded-2xl border border-line">
            <video src="{{ $product->presentation_video }}" controls preload="none" class="aspect-video w-full bg-ink"></video>
        </div>
    @endif
</div>
