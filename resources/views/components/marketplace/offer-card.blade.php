@props(['offer'])

@php
    use Illuminate\Support\Facades\Storage;

    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $hasSale = $offer->sale_price !== null && (float) $offer->sale_price < (float) $offer->price;
    $off = $hasSale ? round(100 - ($offer->sale_price / $offer->price * 100)) : 0;
    $typeLabel = ['physical' => 'Produit', 'digital' => 'Digital', 'service' => 'Service', 'training' => 'Formation'][$offer->type] ?? $offer->type;
    $url = route('marketplace.shop.offer', $offer->slug);
@endphp

<article class="flex flex-col rounded-2xl border border-line bg-surface p-3 shadow-[0_1px_2px_rgba(28,25,23,0.04)]">
    <a href="{{ $url }}" class="relative block overflow-hidden rounded-xl bg-ground-alt" tabindex="-1" aria-hidden="true">
        <div class="aspect-[4/5]">
            @if ($offer->main_image_path)
                <img src="{{ Storage::url($offer->main_image_path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <div class="flex h-full items-center justify-center text-ink-3">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                </div>
            @endif
        </div>
        @if ($off > 0)
            <span class="absolute right-2 top-2 rounded-full bg-rust px-2 py-0.5 text-xs font-semibold text-white">−{{ $off }} %</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col px-1 pb-1 pt-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($offer->vendor?->display_name)
                <x-ui.badge tone="muted" class="max-w-full truncate">{{ $offer->vendor->display_name }}</x-ui.badge>
            @endif
            <x-ui.badge tone="ink">{{ $typeLabel }}</x-ui.badge>
        </div>

        <h3 class="mt-2.5 text-base font-semibold leading-snug text-ink">
            <a href="{{ $url }}" class="line-clamp-2 hover:text-accent-2">{{ $offer->title }}</a>
        </h3>

        @if ($offer->rating_count > 0)
            <p class="mt-1 text-xs text-ink-3">
                Note {{ number_format($offer->rating_avg, 1, ',', ' ') }} / 5 · {{ $offer->rating_count }} avis
            </p>
        @endif

        <div class="mt-2 flex flex-wrap items-baseline gap-2 pb-3">
            <span class="text-base font-semibold {{ $hasSale ? 'text-rust' : 'text-ink' }}">{{ $money($offer->effectivePrice()) }} {{ $offer->currency }}</span>
            @if ($hasSale)<span class="text-sm text-ink-3 line-through">{{ $money($offer->price) }}</span>@endif
        </div>

        <a href="{{ $url }}"
           class="mt-auto inline-flex min-h-11 w-full items-center justify-center rounded-full border border-line-strong px-4 text-sm font-semibold text-ink transition-colors hover:border-ink hover:bg-ink hover:text-white">
            Voir l'offre<span class="sr-only"> : {{ $offer->title }}</span>
        </a>
    </div>
</article>
