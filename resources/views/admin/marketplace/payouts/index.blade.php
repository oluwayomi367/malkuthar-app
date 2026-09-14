@extends('layouts.admin')

@section('title', 'Administration — Versements vendeurs')
@section('eyebrow', 'Marketplace')
@section('heading', 'Versements vendeurs')

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <x-ui.stat-tile label="Commission plateforme (payé)" :value="$money($summary['commission_earned'])" />
    <x-ui.stat-tile label="Dû aux vendeurs (en validation)" :value="$money($summary['vendor_pending'])" />
    <x-ui.stat-tile label="Versé aux vendeurs" :value="$money($summary['vendor_paid'])" />
</div>

<nav class="mb-5 flex flex-wrap gap-2 text-sm">
    @foreach (['requested' => 'À traiter', 'approved' => 'Approuvés', 'paid' => 'Payés', 'failed' => 'Échoués', 'all' => 'Tous'] as $k => $lbl)
        <a href="{{ route('admin.marketplace.payouts.index', ['status' => $k]) }}"
           @class(['rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => $status === $k,
               'border-black/10 text-ink-2 hover:text-ink' => $status !== $k])>{{ $lbl }}</a>
    @endforeach
</nav>

@if ($payouts->isEmpty())
    <x-ui.empty-state title="Aucun versement" text="Aucune demande pour ce filtre." />
@else
    <div class="space-y-3">
        @foreach ($payouts as $p)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-ink">{{ $p->reference }}</p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $p->vendor?->display_name }} · {{ $p->method }}
                            @if ($p->account) · {{ $p->account->label }}@endif
                            · {{ $p->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-ink">{{ $money($p->net_amount) }} {{ $p->currency }}</span>
                        <x-ui.status-pill :status="$p->status" />
                    </div>
                </div>
                @if (in_array($p->status, ['requested', 'approved'], true))
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-black/[0.06] pt-3">
                        @if ($p->status === 'requested')
                            <form method="POST" action="{{ route('admin.marketplace.payouts.approve', $p) }}">@csrf
                                <button class="h-9 rounded-lg border border-black/10 bg-ground-alt px-3 text-xs font-semibold text-ink">Approuver</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.marketplace.payouts.mark-paid', $p) }}" class="flex gap-1">@csrf
                            <input name="provider_reference" placeholder="Réf. prestataire (fac.)" class="h-9 w-44 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <button class="h-9 rounded-lg border border-brand-green/40 bg-brand-green/10 px-3 text-xs font-semibold text-brand-green">Marquer payé</button>
                        </form>
                        <form method="POST" action="{{ route('admin.marketplace.payouts.mark-failed', $p) }}" class="flex gap-1">@csrf
                            <input name="reason" required placeholder="Motif d'échec" class="h-9 w-44 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust">Échoué</button>
                        </form>
                    </div>
                @elseif ($p->failure_reason)
                    <p class="mt-2 text-xs text-rust">Échec : {{ $p->failure_reason }}</p>
                @endif
            </div>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$payouts" class="mt-6" />
@endif
@endsection
