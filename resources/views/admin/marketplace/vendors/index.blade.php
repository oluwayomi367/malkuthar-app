@extends('layouts.admin')

@section('title', 'Administration — Vendeurs')
@section('eyebrow', 'Marketplace')
@section('heading', 'Comptes vendeurs')

@section('content')
<nav class="mb-5 flex flex-wrap gap-2 text-sm">
    @foreach (['' => 'Tous', 'pending' => 'En attente', 'active' => 'Actifs', 'suspended' => 'Suspendus', 'closed' => 'Fermés'] as $k => $lbl)
        <a href="{{ route('admin.marketplace.vendors.index', array_filter(['status' => $k, 'q' => $q ?: null])) }}"
           @class([
               'rounded-full border px-3 py-1.5',
               'border-accent bg-accent/10 font-semibold text-accent' => (string) $status === (string) $k,
               'border-black/10 text-ink-2 hover:text-ink' => (string) $status !== (string) $k,
           ])>
            {{ $lbl }}@if ($k && isset($counts[$k])) <span class="text-ink-3">({{ $counts[$k] }})</span>@endif
        </a>
    @endforeach
</nav>

<form method="GET" class="mb-5 flex flex-wrap gap-2">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input name="q" value="{{ $q }}" placeholder="Nom, raison sociale, email…"
           class="h-10 w-72 max-w-full rounded-lg border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
    <input name="region" value="{{ $region }}" placeholder="Région"
           class="h-10 w-40 rounded-lg border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3">
    <button class="h-10 rounded-lg border border-black/10 bg-ground-alt px-4 text-sm font-semibold text-ink">Filtrer</button>
</form>

@if ($vendors->isEmpty())
    <x-ui.empty-state title="Aucun vendeur" text="Aucun compte ne correspond à ce filtre." />
@else
    <div class="space-y-3">
        @foreach ($vendors as $v)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.marketplace.vendors.show', $v) }}" class="font-semibold text-ink hover:text-accent">{{ $v->display_name }}</a>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ trim("{$v->user?->first_name} {$v->user?->last_name}") }} · {{ $v->user?->email }}
                            @if ($v->region) · {{ $v->region }}@endif
                            · demandé le {{ $v->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($v->commission_rate_override !== null)
                            <span class="text-xs text-ink-3">comm. {{ rtrim(rtrim(number_format($v->commission_rate_override * 100, 2), '0'), '.') }} %</span>
                        @endif
                        <x-ui.status-pill :status="$v->status" />
                    </div>
                </div>

                @if ($v->status === 'pending')
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-black/[0.06] pt-3">
                        <form method="POST" action="{{ route('admin.marketplace.vendors.approve', $v) }}">
                            @csrf
                            <button class="h-9 rounded-lg border border-brand-green/40 bg-brand-green/10 px-3 text-xs font-semibold text-brand-green hover:bg-brand-green/20">Approuver</button>
                        </form>
                        <form method="POST" action="{{ route('admin.marketplace.vendors.reject', $v) }}" class="flex gap-2">
                            @csrf
                            <input name="reason" required placeholder="Motif de refus"
                                   class="h-9 w-56 rounded-lg border border-black/10 bg-surface px-3 text-xs text-ink placeholder:text-ink-3">
                            <button class="h-9 rounded-lg border border-rust/40 bg-rust/10 px-3 text-xs font-semibold text-rust hover:bg-rust/20">Refuser</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <x-ui.pagination :paginator="$vendors" />
@endif
@endsection
