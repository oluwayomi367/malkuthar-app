@extends('layouts.storefront')

@section('title', 'Commander — Place de marché')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
    <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Finaliser la commande</h1>

    <div class="mt-6 grid gap-8 lg:grid-cols-[1.3fr_1fr]">
        <form method="POST" action="{{ route('marketplace.checkout.store') }}" class="grid gap-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field name="email" label="Email" type="email" :required="true"
                            :value="old('email', auth()->user()?->email)" />
                <x-ui.field name="phone" label="Téléphone" :value="old('phone', auth()->user()?->phone)" />
            </div>
            <x-ui.field name="address_line" label="Adresse (livraison physique)" :value="old('address_line')" />
            <x-ui.field name="city" label="Ville" :value="old('city')" />

            <fieldset class="rounded-xl border border-line bg-ground-alt/50 p-3 text-sm">
                <legend class="px-1 text-xs font-semibold uppercase text-ink-3">Paiement</legend>
                <label class="flex items-center gap-2">
                    <input type="radio" name="payment_method" value="cod" checked class="text-accent">
                    Paiement à la livraison / sur place
                </label>
                <p class="mt-1 text-xs text-ink-3">Le paiement en ligne (Mobile Money, carte) arrive prochainement sur la place de marché.</p>
            </fieldset>

            <div>
                <x-ui.button type="submit" variant="primary">Confirmer la commande</x-ui.button>
            </div>
        </form>

        <aside class="rounded-2xl border border-line bg-surface p-4">
            <p class="text-sm font-semibold text-ink">Récapitulatif</p>
            <div class="mt-3 space-y-3 text-sm">
                @foreach ($byVendor as $vendorName => $vendorLines)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-3">{{ $vendorName }}</p>
                        @foreach ($vendorLines as $line)
                            <div class="flex justify-between text-ink-2">
                                <span>{{ $line['quantity'] }} × {{ \Illuminate\Support\Str::limit($line['offer']->title, 26) }}@if ($line['variant']) <span class="text-ink-3">({{ $line['variant']->optionsLabel() }})</span>@endif</span>
                                <span>{{ $money($line['line_total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex justify-between border-t border-line pt-3 font-semibold text-ink">
                <span>Total</span><span>{{ $money($subtotal) }}</span>
            </div>
        </aside>
    </div>
</div>
@endsection
