@props(['order'])

@php
    $steps = ['Payée', 'En préparation', 'Expédiée', 'En livraison', 'Livrée'];
    $index = ['paid' => 0, 'preparing' => 1, 'shipped' => 2, 'in_delivery' => 3, 'delivered' => 4][$order->shipping_status] ?? 0;
@endphp

<div {{ $attributes }}>
    <div class="flex items-center">
        @foreach ($steps as $i => $label)
            <div class="flex flex-1 items-center @if ($i === count($steps) - 1) flex-none @endif">
                <span @class([
                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold',
                    'border-accent bg-accent text-white' => $i <= $index,
                    'border-black/15 text-ink-3' => $i > $index,
                ])>
                    @if ($i < $index)
                        <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 6.5l2.5 2.5 4.5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                @if ($i < count($steps) - 1)
                    <span @class(['h-px flex-1', 'bg-accent' => $i < $index, 'bg-black/[0.06]' => $i >= $index])></span>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-2 flex justify-between text-[10.5px] text-ink-3">
        @foreach ($steps as $i => $label)
            <span @class(['w-12 text-center', 'text-ink' => $i === $index]) @if ($i === 0) class="w-12 text-left" @elseif ($i === count($steps) - 1) class="w-12 text-right" @endif>{{ $label }}</span>
        @endforeach
    </div>

    @if ($order->tracking_number || $order->shipping_carrier)
        <p class="mt-3 text-xs text-ink-2">
            {{ $order->shipping_carrier }}
            @if ($order->tracking_number) · Suivi <span class="font-mono text-ink">{{ $order->tracking_number }}</span>@endif
        </p>
    @endif
</div>
