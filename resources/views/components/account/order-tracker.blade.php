@props(['order'])

@php
    // Quatre étapes après le paiement (mêmes étapes que le suivi côté admin).
    $steps = [
        'preparing' => 'En préparation',
        'shipped' => 'Expédiée',
        'in_delivery' => 'En livraison',
        'delivered' => 'Livrée',
    ];
    $keys = array_keys($steps);
    $status = $order->shipping_status;
    $reached = in_array($status, $keys, true) ? array_search($status, $keys, true) + 1 : 0;
    $current = $reached > 0 ? $steps[$status] : 'Payée, en attente de préparation';
@endphp

<div {{ $attributes }}>
    <p class="text-sm text-ink-2">
        Statut : <span class="font-semibold text-ink">{{ $current }}</span>
    </p>

    <ol class="mt-3 grid grid-cols-4 gap-1.5" aria-label="Étapes de livraison">
        @foreach ($steps as $key => $label)
            @php $done = $loop->iteration <= $reached; @endphp
            <li @if ($loop->iteration === $reached) aria-current="step" @endif>
                <span @class([
                    'block h-1.5 rounded-full',
                    'bg-ink' => $done,
                    'bg-line' => ! $done,
                ])></span>
                <span @class([
                    'mt-2 block text-xs leading-tight',
                    'font-semibold text-ink' => $loop->iteration === $reached,
                    'text-ink-2' => $done && $loop->iteration !== $reached,
                    'text-ink-3' => ! $done,
                ])>
                    {{ $label }}<span class="sr-only">{{ $done ? ' (atteinte)' : ' (à venir)' }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    @if ($order->tracking_number || $order->shipping_carrier)
        <p class="mt-4 text-sm text-ink-2">
            {{ $order->shipping_carrier }}
            @if ($order->tracking_number)
                @if ($order->shipping_carrier) · @endif
                Numéro de suivi <span class="font-mono text-ink">{{ $order->tracking_number }}</span>
            @endif
        </p>
    @endif
</div>
