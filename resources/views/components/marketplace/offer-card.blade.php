@props(['offer'])

@php
    use Illuminate\Support\Facades\Storage;

    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $hasSale = $offer->sale_price !== null && (float) $offer->sale_price < (float) $offer->price;
    $off = $hasSale ? round(100 - ($offer->sale_price / $offer->price * 100)) : 0;
    $typeLabel = ['physical' => 'Produit', 'digital' => 'Digital', 'service' => 'Service', 'training' => 'Formation'][$offer->type] ?? $offer->type;
@endphp

<a href="{{ route('marketplace.shop.offer', $offer->slug) }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-[0_16px_40px_-24px_rgba(46,44,40,0.32)]">
    <div class="relative aspect-[4/3] overflow-hidden bg-ground-alt">
        @if ($offer->main_image_path)
            <img src="{{ Storage::url($offer->main_image_path) }}" alt="{{ $offer->title }}"
                 loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
        @else
            <div class="flex h-full items-center justify-center text-line-strong">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
            </div>
        @endif
        <span class="absolute left-2 top-2 rounded-full bg-ground/90 px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-wide text-ink-2 backdrop-blur">{{ $typeLabel }}</span>
        @if ($off > 0)
            <span class="absolute right-2 top-2 rounded-full bg-rust px-2 py-0.5 text-[11px] font-bold text-white">−{{ $off }}%</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-3.5">
        <p class="truncate text-[11px] text-ink-3">{{ $offer->vendor?->display_name }}</p>
        <p class="mt-0.5 line-clamp-2 font-display text-[15px] font-semibold leading-snug text-ink">{{ $offer->title }}</p>

        @if ($offer->rating_count > 0)
            <p class="mt-1 flex items-center gap-1 text-[11px] text-ink-3">
                <span class="text-accent">{{ str_repeat('★', (int) round($offer->rating_avg)) }}<span class="text-line-strong">{{ str_repeat('★', 5 - (int) round($offer->rating_avg)) }}</span></span>
                <span>{{ number_format($offer->rating_avg, 1, ',', ' ') }} · {{ $offer->rating_count }}</span>
            </p>
        @endif

        <div class="mt-auto flex items-baseline gap-2 pt-3">
            <span class="font-display font-semibold text-ink">{{ $money($offer->effectivePrice()) }} {{ $offer->currency }}</span>
            @if ($hasSale)<span class="text-xs text-ink-3 line-through">{{ $money($offer->price) }}</span>@endif
        </div>
    </div>
</a>
