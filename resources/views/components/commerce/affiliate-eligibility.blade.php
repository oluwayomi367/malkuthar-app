@props(['product'])

@php
    if ($product->type !== 'digital' || ! $product->is_affiliable) {
        return;
    }
    $rates = config('malkuthar.affiliation.commission_rates', [1 => 0.15, 2 => 0.10, 3 => 0.05]);
    $pct = fn ($r) => rtrim(rtrim(number_format((float) $r * 100, 1, ',', ' '), '0'), ',');
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-accent/25 bg-accent-soft p-4']) }}>
    <div class="flex items-start gap-2.5">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-ink">Ce produit est affiliable</p>
            <p class="mt-1 text-sm leading-relaxed text-ink-2">
                Partagez-le et gagnez <strong class="text-ink">{{ $pct($rates[1]) }} %</strong> ·
                <strong class="text-ink">{{ $pct($rates[2]) }} %</strong> ·
                <strong class="text-ink">{{ $pct($rates[3]) }} %</strong> sur trois niveaux.
                <a href="{{ route('affiliate.program-terms') }}" class="whitespace-nowrap font-medium text-ink underline decoration-accent underline-offset-2">Voir les conditions</a>
            </p>
        </div>
    </div>
</div>
