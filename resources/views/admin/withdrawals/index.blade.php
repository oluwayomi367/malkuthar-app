@extends('layouts.admin')

@section('title', 'Administration — Retraits')
@section('eyebrow', 'Finance')
@section('heading', 'Retraits')

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<nav class="mb-6 flex gap-2 text-sm">
    @foreach (['' => 'Tous', 'processing' => 'À traiter', 'paid' => 'Payés', 'failed' => 'Échoués'] as $k => $lbl)
        <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $k])) }}"
           @class([
               'rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => (string) $status === (string) $k,
               'border-black/10 text-ink-2 hover:text-ink' => (string) $status !== (string) $k,
           ])>{{ $lbl }}</a>
    @endforeach
</nav>

@if ($withdrawals->isEmpty())
    <x-ui.empty-state title="Aucun retrait" text="Aucune demande ne correspond à ce filtre." />
@else
    <div class="space-y-3">
        @foreach ($withdrawals as $w)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-ink">{{ $w->reference }}</p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $w->user?->affiliate_id }} — {{ trim("{$w->user?->first_name} {$w->user?->last_name}") }}
                            · {{ $w->method }} · {{ $w->requested_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="font-semibold text-ink">{{ $m($w->amount) }} {{ $w->currency }}</p>
                            <p class="text-[11px] text-ink-3">net {{ $m($w->net_amount) }}</p>
                        </div>
                        <x-ui.status-pill :status="$w->status" />
                    </div>
                </div>

                @if ($w->status === 'processing')
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-black/[0.06] pt-3">
                        <form method="POST" action="{{ route('admin.withdrawals.mark-paid', $w) }}" class="flex gap-2">
                            @csrf
                            <input name="provider_reference" placeholder="Réf. prestataire (facultatif)"
                                   class="h-9 rounded-lg border border-black/10 bg-surface px-3 text-xs text-ink placeholder:text-ink-3">
                            <button class="h-9 rounded-lg border border-emerald-400/40 bg-emerald-400/10 px-3 text-xs font-semibold text-emerald-300 hover:bg-emerald-400/20">Marquer payé</button>
                        </form>
                        <form method="POST" action="{{ route('admin.withdrawals.mark-failed', $w) }}" class="flex gap-2">
                            @csrf
                            <input name="reason" required placeholder="Motif d'échec"
                                   class="h-9 rounded-lg border border-black/10 bg-surface px-3 text-xs text-ink placeholder:text-ink-3">
                            <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust hover:bg-rust-tint">Marquer échoué</button>
                        </form>
                    </div>
                @elseif ($w->failure_reason)
                    <p class="mt-2 text-xs text-rust">Échec : {{ $w->failure_reason }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <x-ui.pagination :paginator="$withdrawals" />
@endif
@endsection
