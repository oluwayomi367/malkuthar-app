@extends('layouts.admin')

@section('title', 'Administration — '.$vendor->display_name)
@section('eyebrow', 'Marketplace · Vendeur')
@section('heading', $vendor->display_name)

@section('content')
@php $pct = fn ($r) => $r === null ? '—' : rtrim(rtrim(number_format($r * 100, 2), '0'), '.').' %'; @endphp

<div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
    <div class="space-y-6">
        {{-- Profil --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Profil</h2>
                <x-ui.status-pill :status="$vendor->status" />
            </div>
            <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-3">Compte</dt><dd class="text-ink">{{ trim("{$vendor->user?->first_name} {$vendor->user?->last_name}") }} — {{ $vendor->user?->email }}</dd></div>
                <div><dt class="text-ink-3">Raison sociale</dt><dd class="text-ink">{{ $vendor->legal_name ?: '—' }}</dd></div>
                <div><dt class="text-ink-3">Région / ville</dt><dd class="text-ink">{{ $vendor->region ?: '—' }}@if ($vendor->city) · {{ $vendor->city }}@endif</dd></div>
                <div><dt class="text-ink-3">Contact</dt><dd class="text-ink">{{ $vendor->contact_email ?: '—' }} · {{ $vendor->phone ?: '—' }}</dd></div>
                <div><dt class="text-ink-3">Devise de règlement</dt><dd class="text-ink">{{ $vendor->payout_currency }}</dd></div>
                <div><dt class="text-ink-3">Validé par</dt><dd class="text-ink">{{ $vendor->approver ? trim("{$vendor->approver->first_name} {$vendor->approver->last_name}") : '—' }} {{ optional($vendor->approved_at)->format('d/m/Y') }}</dd></div>
            </dl>
            @if ($vendor->bio)
                <p class="mt-3 border-t border-black/[0.06] pt-3 text-sm text-ink-2">{{ $vendor->bio }}</p>
            @endif
            @if ($vendor->suspension_reason)
                <p class="mt-3 text-sm text-rust">Motif : {{ $vendor->suspension_reason }}</p>
            @endif
        </section>

        {{-- Justificatifs --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Justificatifs (KYC)</h2>
            @if ($vendor->documents->isEmpty())
                <p class="mt-2 text-sm text-ink-3">Aucune pièce déposée.</p>
            @else
                <ul class="mt-3 divide-y divide-black/[0.06] text-sm">
                    @foreach ($vendor->documents as $doc)
                        <li class="flex items-center justify-between py-2">
                            <span class="text-ink">{{ $doc->type }} — {{ $doc->original_name ?: 'pièce' }}</span>
                            <x-ui.status-pill :status="$doc->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Compte</h2>
            <div class="mt-3 flex flex-col gap-2">
                @if ($vendor->status === 'pending')
                    <form method="POST" action="{{ route('admin.marketplace.vendors.approve', $vendor) }}">@csrf
                        <button class="h-10 w-full rounded-xl border border-brand-green/40 bg-brand-green/10 text-sm font-semibold text-brand-green hover:bg-brand-green/20">Approuver le compte</button>
                    </form>
                    <form method="POST" action="{{ route('admin.marketplace.vendors.reject', $vendor) }}" class="space-y-2">@csrf
                        <input name="reason" required placeholder="Motif de refus" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                        <button class="h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust hover:bg-rust-tint">Refuser</button>
                    </form>
                @elseif ($vendor->status === 'active')
                    <form method="POST" action="{{ route('admin.marketplace.vendors.suspend', $vendor) }}" class="space-y-2">@csrf
                        <input name="reason" required placeholder="Motif de suspension" class="h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                        <button class="h-10 w-full rounded-xl border border-rust/40 bg-rust/10 text-sm font-semibold text-rust hover:bg-rust-tint">Suspendre</button>
                    </form>
                @elseif (in_array($vendor->status, ['suspended', 'closed'], true))
                    <form method="POST" action="{{ route('admin.marketplace.vendors.reactivate', $vendor) }}">@csrf
                        <button class="h-10 w-full rounded-xl border border-brand-green/40 bg-brand-green/10 text-sm font-semibold text-brand-green hover:bg-brand-green/20">Réactiver</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <h2 class="font-display text-base font-semibold text-ink">Commission</h2>
            <p class="mt-1 text-xs text-ink-3">Taux spécifique au vendeur. Vide = barème catégorie / plateforme. Actuel : {{ $pct($vendor->commission_rate_override) }}.</p>
            <form method="POST" action="{{ route('admin.marketplace.vendors.commission', $vendor) }}" class="mt-3 flex gap-2">
                @csrf
                @method('PUT')
                <input name="commission_rate_percent" type="number" step="0.01" min="0" max="90"
                       value="{{ $vendor->commission_rate_override !== null ? number_format($vendor->commission_rate_override * 100, 2, '.', '') : '' }}"
                       placeholder="%" class="h-10 w-24 rounded-xl border border-black/10 bg-surface px-3 text-sm">
                <button class="h-10 rounded-xl border border-black/10 bg-ground-alt px-4 text-sm font-semibold text-ink">Enregistrer</button>
            </form>
        </section>
    </div>
</div>

<p class="mt-6 text-sm"><a href="{{ route('admin.marketplace.vendors.index') }}" class="text-accent hover:underline">← Tous les vendeurs</a></p>
@endsection
