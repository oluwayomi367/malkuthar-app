@props([
    'variant' => 'default',
])

@php
    $delivery = match ($variant) {
        'digital' => 'Livraison digitale immédiate après paiement',
        'physical' => 'Expédition suivie, numéro de suivi communiqué',
        'service' => 'Lien de visioconférence ou lieu confirmé à la réservation',
        default => 'Livraison digitale immédiate · expédition suivie pour le physique',
    };

    $items = [
        ['Paiement sécurisé', 'FedaPay · CinetPay — Mobile Money &amp; carte'],
        ['Livraison', $delivery],
        ['Support', 'Une équipe joignable en cas de besoin'],
    ];
@endphp

<ul {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-3']) }}>
    @foreach ($items as [$title, $text])
        <li class="flex gap-3">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent-2" aria-hidden="true">
                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 8.5l3 3 7-8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-ink">{{ $title }}</p>
                <p class="text-sm leading-relaxed text-ink-2">{!! $text !!}</p>
            </div>
        </li>
    @endforeach
</ul>
