@extends('layouts.admin')

@section('title', 'Administration — Tableau de bord')
@section('eyebrow', 'MALKUTHAR')
@section('heading', 'Tableau de bord')

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

{{-- ===== KPIs ===== --}}
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-tile label="Affiliés" :value="$m($stats['affiliates_total'])" :emphasis="true">
        <x-slot:foot>
            <span class="text-emerald-300">{{ $m($stats['affiliates_active']) }} actifs</span>
            · {{ $m($stats['affiliates_new']) }} ce mois
            @if ($stats['affiliates_suspended']) · <span class="text-rust">{{ $m($stats['affiliates_suspended']) }} suspendus</span>@endif
        </x-slot:foot>
    </x-ui.stat-tile>

    <x-ui.stat-tile label="Chiffre d'affaires" :value="$m($stats['revenue'])" currency="FCFA">
        <x-slot:foot>
            {{ $m($stats['orders_paid']) }} commandes payées
            @if ($stats['cod_to_collect_count'])
                · <a href="{{ route('admin.orders.index', ['provider' => 'cod', 'payment_status' => 'pending']) }}" class="text-amber hover:underline">{{ $m($stats['cod_to_collect_amount']) }} à encaisser (COD)</a>
            @endif
        </x-slot:foot>
    </x-ui.stat-tile>

    <x-ui.stat-tile label="Commissions générées" :value="$m($stats['commissions_generated'])" currency="FCFA">
        <x-slot:foot>
            <span class="text-amber">{{ $m($stats['commissions_pending']) }} en attente</span>
            · <span class="text-emerald-300">{{ $m($stats['commissions_available']) }} disponibles</span>
        </x-slot:foot>
    </x-ui.stat-tile>

    <x-ui.stat-tile label="Retraits" :value="$m($stats['withdrawals_requested'])">
        <x-slot:foot>
            <span class="text-amber">{{ $m($stats['withdrawals_processing']) }} à traiter</span>
            · {{ $m($stats['withdrawals_paid']) }} payés
            @if ($stats['withdrawals_failed']) · <span class="text-rust">{{ $m($stats['withdrawals_failed']) }} échoués</span>@endif
        </x-slot:foot>
    </x-ui.stat-tile>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-2">
    {{-- Retraits à traiter --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-ink">Retraits à traiter</h2>
            <a href="{{ route('admin.withdrawals.index') }}" class="text-sm text-ink-2 hover:text-ink">Tout voir →</a>
        </div>
        @forelse ($pendingWithdrawals as $w)
            <div class="mb-2 flex items-center justify-between rounded-xl border border-black/10 bg-surface px-4 py-3 text-sm">
                <div>
                    <p class="font-mono text-ink">{{ $w->reference }}</p>
                    <p class="text-xs text-ink-3">{{ $w->user?->affiliate_id }} · {{ $w->method }}</p>
                </div>
                <p class="font-semibold text-ink">{{ $m($w->amount) }} {{ $w->currency }}</p>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-black/10 bg-surface px-4 py-6 text-center text-sm text-ink-3">Aucun retrait en attente.</p>
        @endforelse
    </section>

    {{-- Derniers affiliés --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-ink">Derniers affiliés</h2>
            <a href="{{ route('admin.affiliates.index') }}" class="text-sm text-ink-2 hover:text-ink">Tout voir →</a>
        </div>
        @forelse ($recentAffiliates as $a)
            <a href="{{ route('admin.affiliates.show', $a) }}" class="mb-2 flex items-center justify-between rounded-xl border border-black/10 bg-surface px-4 py-3 text-sm transition-colors hover:border-black/20">
                <div>
                    <p class="text-ink">{{ trim("{$a->first_name} {$a->last_name}") }}</p>
                    <p class="font-mono text-xs text-ink-3">{{ $a->affiliate_id }}</p>
                </div>
                <x-ui.status-pill :status="$a->status === 'active' ? 'available' : 'failed'" />
            </a>
        @empty
            <p class="rounded-xl border border-dashed border-black/10 bg-surface px-4 py-6 text-center text-sm text-ink-3">Aucun affilié.</p>
        @endforelse
    </section>
</div>

{{-- Journal récent --}}
<section class="mt-8">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-ink">Activité récente</h2>
        <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-ink-2 hover:text-ink">Journal complet →</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-black/10">
        @forelse ($recentAudit as $log)
            <div class="flex items-start justify-between gap-4 border-b border-black/[0.06] px-4 py-3 text-sm last:border-0">
                <div class="min-w-0">
                    <p class="text-ink">{{ $log->description }}</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-3">{{ $log->action }}</p>
                </div>
                <p class="shrink-0 text-xs text-ink-3">
                    {{ optional($log->admin)->first_name }} · {{ $log->created_at?->format('d/m H:i') }}
                </p>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-ink-3">Aucune action enregistrée.</p>
        @endforelse
    </div>
</section>
@endsection
