@extends('layouts.admin')

@section('title', 'Administration — Taux de commission')
@section('eyebrow', 'Affiliation')
@section('heading', 'Taux de commission')

@section('content')
@php
    $pct = fn ($r) => rtrim(rtrim(number_format((float) $r * 100, 2, ',', ' '), '0'), ',');
    $current = $resolution['rates'];
@endphp

<div class="mb-6 grid gap-4 md:grid-cols-2">
    {{-- Taux en vigueur --}}
    <div class="rounded-2xl border border-accent/25 bg-accent-soft/40 p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Taux appliqués en ce moment</p>
        <p class="mt-1 font-display text-2xl font-bold text-ink">
            {{ (int) round($current[1] * 100) }} % · {{ (int) round($current[2] * 100) }} % · {{ (int) round($current[3] * 100) }} %
        </p>
        <p class="mt-1 text-sm text-ink-2">
            @if ($resolution['campaign'])
                Campagne « {{ $resolution['campaign']->name }} »
            @else
                Taux standards (config) — aucune campagne active
            @endif
        </p>
    </div>

    {{-- §42 — période de validation --}}
    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Période de validation (§42)</p>
        <p class="mt-1 text-xs text-ink-3">Nombre de jours après la vente avant qu'une commission « en attente » devienne retirable.</p>
        <form method="POST" action="{{ route('admin.commission-campaigns.validation-delay') }}" class="mt-3 flex items-end gap-2">
            @csrf @method('PUT')
            <x-ui.field name="validation_delay_days" label="Jours" type="number" min="0" max="365"
                :value="$validationDelayDays" :required="true" />
            <x-ui.button type="submit" variant="secondary">Enregistrer</x-ui.button>
        </form>
        @error('validation_delay_days')<p class="mt-1 text-xs text-rust">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-8 lg:grid-cols-[1fr_320px] lg:gap-10">
    {{-- Liste --}}
    <div>
        @if ($campaigns->isEmpty())
            <x-ui.empty-state title="Aucune campagne" text="Sans campagne, les taux standards 15 / 10 / 5 % s'appliquent." />
        @else
            <div class="overflow-x-auto rounded-2xl border border-black/10">
                <table class="w-full min-w-[620px] text-left text-sm">
                    <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Campagne</th>
                            <th class="px-4 py-3 font-semibold">Taux (N1 / N2 / N3)</th>
                            <th class="px-4 py-3 font-semibold">Période</th>
                            <th class="px-4 py-3 font-semibold">Prio.</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/[0.06]">
                        @foreach ($campaigns as $c)
                            <tr @class(['opacity-50' => ! $c->is_active])>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-ink">{{ $c->name }}</span>
                                    @if ($c->is_default)<x-ui.badge tone="accent" class="ml-1">par défaut</x-ui.badge>@endif
                                    @unless ($c->is_active)<x-ui.badge tone="muted" class="ml-1">inactive</x-ui.badge>@endunless
                                </td>
                                <td class="px-4 py-3 font-mono text-ink-2">{{ $pct($c->rate_l1) }} / {{ $pct($c->rate_l2) }} / {{ $pct($c->rate_l3) }}</td>
                                <td class="px-4 py-3 text-ink-3">
                                    {{ $c->starts_at?->format('d/m/y') ?? '—' }} → {{ $c->ends_at?->format('d/m/y') ?? '∞' }}
                                </td>
                                <td class="px-4 py-3 text-ink-2">{{ $c->priority }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <form method="POST" action="{{ route('admin.commission-campaigns.update', $c) }}">
                                            @csrf @method('PUT')
                                            @if ($c->is_default)
                                                <button name="action" value="clear_default" class="text-xs text-ink-3 hover:underline">Retirer défaut</button>
                                            @else
                                                <button name="action" value="make_default" class="text-xs text-accent hover:underline">Définir par défaut</button>
                                            @endif
                                        </form>
                                        <form method="POST" action="{{ route('admin.commission-campaigns.update', $c) }}">
                                            @csrf @method('PUT')
                                            <button name="action" value="{{ $c->is_active ? 'deactivate' : 'activate' }}" class="text-xs text-ink-2 hover:underline">{{ $c->is_active ? 'Désactiver' : 'Activer' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.commission-campaigns.destroy', $c) }}" onsubmit="return confirm('Supprimer cette campagne ?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-rust hover:underline">Suppr.</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @error('campaign')<p class="mt-2 text-sm text-rust">{{ $message }}</p>@enderror
    </div>

    {{-- Création --}}
    <div class="lg:sticky lg:top-20 lg:self-start">
        <div class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Nouvelle campagne</p>
            <form method="POST" action="{{ route('admin.commission-campaigns.store') }}" class="mt-3 grid gap-3">
                @csrf
                <x-ui.field name="name" label="Nom" :required="true" placeholder="Campagne B" />
                <div class="grid grid-cols-3 gap-2">
                    <x-ui.field name="rate_l1" label="N1 %" type="number" step="0.01" :value="15" :required="true" />
                    <x-ui.field name="rate_l2" label="N2 %" type="number" step="0.01" :value="10" :required="true" />
                    <x-ui.field name="rate_l3" label="N3 %" type="number" step="0.01" :value="5" :required="true" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <x-ui.field name="starts_at" label="Début" type="date" hint="Facultatif" />
                    <x-ui.field name="ends_at" label="Fin" type="date" hint="Facultatif" />
                </div>
                <x-ui.field name="priority" label="Priorité" type="number" :value="0" hint="La + haute gagne si chevauchement" />
                <label class="flex items-center gap-2 text-sm text-ink-2">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-black/20 text-accent">
                    Définir comme campagne par défaut
                </label>
                <x-ui.button variant="primary" type="submit" :full="true">Créer la campagne</x-ui.button>
            </form>
        </div>
    </div>
</div>
@endsection
