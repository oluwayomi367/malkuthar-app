@props(['product'])

@php
    if ($product->type !== 'digital' || ! $product->is_affiliable) {
        return;
    }
    $rates = config('malkuthar.affiliation.commission_rates', [1 => 0.15, 2 => 0.10, 3 => 0.05]);
    $pct = fn ($r) => rtrim(rtrim(number_format((float) $r * 100, 1, ',', ' '), '0'), ',');
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-gold/35 bg-gold-tint p-4']) }}>
    <div class="flex items-start gap-2.5">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-gold" viewBox="0 0 16 16" fill="currentColor">
            <path d="M11 3a2 2 0 1 0-1.9 2.7L6.3 8A2 2 0 1 0 6.3 10l2.8 2.3A2 2 0 1 0 11 10l-3-2.4 3-2.4A2 2 0 0 0 11 3z"/>
        </svg>
        <div>
            <p class="text-[13px] font-semibold text-ink">Ce produit est affiliable</p>
            <p class="mt-0.5 text-[13px] text-ink-2">
                Partagez-le et gagnez <strong class="text-ink">{{ $pct($rates[1]) }} %</strong> ·
                <strong class="text-ink">{{ $pct($rates[2]) }} %</strong> ·
                <strong class="text-ink">{{ $pct($rates[3]) }} %</strong> sur trois niveaux.
                <a href="{{ route('affiliate.program-terms') }}" class="whitespace-nowrap underline decoration-gold underline-offset-2">Conditions →</a>
            </p>
        </div>
    </div>
</div>
