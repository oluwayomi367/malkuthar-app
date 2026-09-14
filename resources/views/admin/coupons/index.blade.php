@extends('layouts.admin')

@section('title', 'Administration — Coupons')
@section('eyebrow', 'Marketing')
@section('heading', 'Coupons')

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="grid gap-8 lg:grid-cols-[1fr_340px] lg:gap-12">
    {{-- Liste --}}
    <div>
        @if ($coupons->isEmpty())
            <x-ui.empty-state title="Aucun coupon" text="Créez votre premier code de réduction." />
        @else
            <div class="overflow-x-auto rounded-2xl border border-black/10">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Code</th>
                            <th class="px-4 py-3 font-semibold">Remise</th>
                            <th class="px-4 py-3 font-semibold">Utilisations</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/[0.06]">
                        @foreach ($coupons as $c)
                            <tr>
                                <td class="px-4 py-3 font-mono text-ink">{{ $c->code }}</td>
                                <td class="px-4 py-3 text-ink-2">{{ $c->type === 'percent' ? $m($c->value).' %' : $m($c->value).' FCFA' }}</td>
                                <td class="px-4 py-3 text-ink-2">{{ $c->used_count }}{{ $c->max_uses ? ' / '.$c->max_uses : '' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.status-pill :status="$c->is_active ? 'available' : 'cancelled'" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($c->is_active)
                                        <form method="POST" action="{{ route('admin.coupons.destroy', $c) }}">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-rust hover:underline">Désactiver</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :paginator="$coupons" />
        @endif
    </div>

    {{-- Création --}}
    <div class="lg:sticky lg:top-20 lg:self-start">
        <div class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Nouveau coupon</p>
            <form method="POST" action="{{ route('admin.coupons.store') }}" class="mt-3 grid gap-3">
                @csrf
                <x-ui.field name="code" label="Code" :required="true" />
                <x-ui.select name="type" label="Type" :required="true" :options="['percent' => 'Pourcentage', 'fixed' => 'Montant fixe']" />
                <x-ui.field name="value" label="Valeur" type="number" :required="true" hint="% ou FCFA selon le type" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field name="max_uses" label="Max. utilisations" type="number" hint="Facultatif" />
                    <x-ui.field name="min_order_amount" label="Min. commande" type="number" hint="Facultatif" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field name="starts_at" label="Début" type="date" />
                    <x-ui.field name="expires_at" label="Fin" type="date" />
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-2">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-black/20 bg-black/[0.04] text-accent">
                    Actif
                </label>
                <x-ui.button variant="primary" type="submit" :full="true">Créer le coupon</x-ui.button>
            </form>
        </div>
    </div>
</div>
@endsection
