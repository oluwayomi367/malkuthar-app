@extends('layouts.admin')

@section('title', 'Administration — ' . $affiliate->affiliate_id)
@section('eyebrow', 'Affilié')
@section('heading', trim("{$affiliate->first_name} {$affiliate->last_name}"))

@section('actions')
    <x-ui.button :href="route('admin.affiliates.index')" variant="secondary">← Liste</x-ui.button>
@endsection

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $statusMeta = ['active' => ['Actif', 'accent'], 'suspended' => ['Suspendu', 'rust']];
    [$sLabel, $sTone] = $statusMeta[$affiliate->status] ?? [ucfirst((string) $affiliate->status), 'muted'];
    $payTone = ['paid' => 'paid', 'pending' => 'pending', 'failed' => 'failed', 'refunded' => 'reversed', 'cancelled' => 'cancelled'];
@endphp

<div class="flex flex-wrap items-center gap-3 text-sm text-ink-2">
    <span class="font-mono text-ink">{{ $affiliate->affiliate_id }}</span>
    <span class="text-ink-3">·</span>
    <span>{{ $affiliate->email }}</span>
    <x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge>
</div>

{{-- ============ Profil + Parrain ============ --}}
<div class="mt-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Profil</p>
        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
            <dt class="text-ink-3">Nom</dt><dd class="text-ink">{{ trim("{$affiliate->first_name} {$affiliate->last_name}") ?: '—' }}</dd>
            <dt class="text-ink-3">E-mail</dt><dd class="break-all text-ink">{{ $affiliate->email }}</dd>
            <dt class="text-ink-3">Téléphone</dt><dd class="text-ink">{{ $affiliate->phone ?: '—' }}</dd>
            <dt class="text-ink-3">Pays</dt><dd class="text-ink">{{ $affiliate->country ?: '—' }}</dd>
            <dt class="text-ink-3">Devise</dt><dd class="text-ink">{{ $affiliate->currency ?: '—' }}</dd>
            <dt class="text-ink-3">Identifiant affilié</dt><dd class="font-mono text-ink">{{ $affiliate->affiliate_id }}</dd>
            <dt class="text-ink-3">Code de parrainage</dt><dd class="font-mono text-ink">{{ $affiliate->code_parrainage ?: '—' }}</dd>
            <dt class="text-ink-3">Statut</dt><dd><x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge></dd>
            <dt class="text-ink-3">Inscrit le</dt><dd class="text-ink">{{ $affiliate->created_at?->format('d/m/Y') }}</dd>
            <dt class="text-ink-3">Affilié depuis</dt><dd class="text-ink">{{ $affiliate->affiliate_activated_at?->format('d/m/Y') ?: '—' }}</dd>
        </dl>
    </div>

    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Parrain</p>
        @if ($affiliate->parent)
            <div class="mt-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-ink">{{ trim("{$affiliate->parent->first_name} {$affiliate->parent->last_name}") ?: 'Affilié' }}</p>
                    <p class="mt-0.5 font-mono text-xs text-ink-3">{{ $affiliate->parent->affiliate_id }}</p>
                </div>
                <a href="{{ route('admin.affiliates.show', $affiliate->parent) }}" class="text-sm font-semibold text-accent hover:underline">Ouvrir la fiche →</a>
            </div>
        @else
            <p class="mt-3 text-sm text-ink-3">Aucun parrain — cet affilié est une racine de réseau.</p>
        @endif

        <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Portefeuille</p>
        <div class="mt-2 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-lg border border-black/10 py-2">
                <p class="font-display text-base font-bold text-ink">{{ $m($totals['ledger_balance']) }}</p>
                <p class="text-[10px] uppercase tracking-wide text-ink-3">Solde (ledger)</p>
            </div>
            <div class="rounded-lg border border-black/10 py-2">
                <p class="font-display text-base font-bold text-ink-2">{{ $m($totals['commissions']) }}</p>
                <p class="text-[10px] uppercase tracking-wide text-ink-3">Commissions</p>
            </div>
            <div class="rounded-lg border border-black/10 py-2">
                <p class="font-display text-base font-bold text-ink-2">{{ $m($totals['withdrawn']) }}</p>
                <p class="text-[10px] uppercase tracking-wide text-ink-3">Retiré</p>
            </div>
        </div>
    </div>
</div>

{{-- ============ Compte + Ajustement §49 ============ --}}
<div class="mt-4 grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Compte — suspension (§63)</p>
        @if ($affiliate->status !== 'suspended')
            @php $inputCls = 'h-10 w-full rounded-lg border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-rust focus:outline-none focus:ring-2 focus:ring-rust/20'; @endphp
            <form method="POST" action="{{ route('admin.affiliates.suspend', $affiliate) }}" class="mt-3 space-y-3">
                @csrf
                <label class="block text-[12px] font-medium text-ink-2">Motif
                    <select name="category" required class="{{ $inputCls }} mt-1">
                        @foreach (\App\Services\AffiliateSuspensionService::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-[12px] font-medium text-ink-2">Détail
                    <textarea name="reason" required rows="2" placeholder="Description de la fraude / manipulation constatée…"
                              class="{{ $inputCls }} mt-1 h-auto py-2"></textarea>
                </label>

                <p class="pt-1 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Traitement financier</p>
                <label class="block text-[12px] text-ink-2">Commissions en attente
                    <select name="treatment_pending" class="{{ $inputCls }} mt-1">
                        <option value="freeze">Geler — restent « en attente » jusqu'à réactivation</option>
                        <option value="cancel">Annuler</option>
                    </select>
                </label>
                <label class="block text-[12px] text-ink-2">Commissions disponibles
                    <select name="treatment_available" class="{{ $inputCls }} mt-1">
                        <option value="freeze">Geler — conservées, retrait bloqué</option>
                        <option value="clawback">Reprendre — débit tracé du solde</option>
                    </select>
                </label>
                <label class="block text-[12px] text-ink-2">Retraits en cours
                    <select name="treatment_withdrawals" class="{{ $inputCls }} mt-1">
                        <option value="hold">Laisser se terminer</option>
                        <option value="rollback">Annuler et recréditer le solde</option>
                    </select>
                </label>

                <button class="inline-flex h-10 items-center justify-center rounded-xl border border-rust/40 bg-rust/10 px-4 text-sm font-semibold text-rust hover:bg-rust/20">Suspendre le compte</button>
                <p class="text-[11px] text-ink-3">Aucune donnée financière n'est supprimée : tout passe par des transitions de statut et des écritures de registre.</p>
            </form>
        @else
            <div class="mt-3 rounded-lg border border-rust/25 bg-rust/[0.06] p-3 text-sm">
                <p class="font-semibold text-rust">Compte suspendu</p>
                <p class="mt-1 text-ink-2">
                    Motif : {{ \App\Services\AffiliateSuspensionService::CATEGORIES[$affiliate->suspension_category] ?? $affiliate->suspension_category ?? '—' }}
                    @if ($affiliate->suspended_at) · le {{ $affiliate->suspended_at->format('d/m/Y') }} @endif
                </p>
                @if ($affiliate->suspension_reason)<p class="mt-1 text-ink-2">« {{ $affiliate->suspension_reason }} »</p>@endif
                <p class="mt-1 text-xs text-ink-3">Retraits bloqués, commissions en attente gelées. Données financières conservées.</p>
            </div>
            <form method="POST" action="{{ route('admin.affiliates.reactivate', $affiliate) }}" class="mt-3">
                @csrf
                <button class="inline-flex h-10 items-center justify-center rounded-xl border border-brand-green/40 bg-brand-green/10 px-4 text-sm font-semibold text-brand-green hover:bg-brand-green/20">Réactiver le compte</button>
            </form>
        @endif
    </div>

    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Ajustement du solde (§49)</p>
        <p class="mt-1 text-xs text-ink-3">Crée une écriture de ledger tracée. Jamais d'écriture directe sur le solde.</p>
        <form method="POST" action="{{ route('admin.affiliates.wallet-adjustments.store', $affiliate) }}" class="mt-3 grid gap-3">
            @csrf
            <div class="flex gap-2">
                <select name="direction" class="h-11 rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none">
                    <option value="credit">Créditer +</option>
                    <option value="debit">Débiter −</option>
                </select>
                <input name="amount" type="number" step="0.01" min="0.01" required placeholder="Montant"
                       class="h-11 flex-1 rounded-xl border border-black/10 bg-surface px-3.5 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
            </div>
            <input name="reason" required placeholder="Motif"
                   class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
            <x-ui.button variant="primary" type="submit">Enregistrer l'ajustement</x-ui.button>
        </form>
    </div>
</div>

{{-- ============ Lignée ============ --}}
<section class="mt-8">
    <h2 class="mb-3 font-display text-lg font-semibold text-ink">Lignée</h2>
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ($lineage as $level)
            <div class="rounded-xl border border-black/10 bg-surface p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-ink">Niveau {{ $level['level'] }}</p>
                    <span class="rounded-full border border-accent/30 bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">{{ $level['rate'] }} %</span>
                </div>
                <dl class="mt-2 space-y-1 text-sm text-ink-2">
                    <div class="flex justify-between"><dt>Filleuls</dt><dd class="text-ink">{{ $m($level['members']) }}</dd></div>
                    <div class="flex justify-between"><dt>Actifs</dt><dd class="text-ink">{{ $m($level['active']) }}</dd></div>
                    <div class="flex justify-between"><dt>Ventes réalisées</dt><dd class="text-ink">{{ $m($level['sales'] ?? 0) }}</dd></div>
                    <div class="flex justify-between"><dt>CA généré</dt><dd class="text-ink">{{ $m($level['revenue']) }} {{ $totals['currency'] }}</dd></div>
                </dl>
            </div>
        @endforeach
    </div>

    @if ($children->isNotEmpty())
        <p class="mt-4 mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Filleuls directs</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($children as $child)
                <a href="{{ route('admin.affiliates.show', $child) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-black/10 px-3 py-1.5 text-sm text-ink hover:border-black/20 hover:bg-black/[0.03]">
                    {{ trim("{$child->first_name} {$child->last_name}") ?: $child->affiliate_id }}
                    <span class="font-mono text-xs text-ink-3">{{ $child->affiliate_id }}</span>
                    @if ($child->status !== 'active')<span class="text-xs text-rust">suspendu</span>@endif
                </a>
            @endforeach
        </div>
    @endif
</section>

{{-- ============ Ventes ============ --}}
<section class="mt-8">
    <h2 class="mb-3 font-display text-lg font-semibold text-ink">Ventes</h2>
    <div class="overflow-x-auto rounded-2xl border border-black/10">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                <tr>
                    <th class="px-4 py-3 font-semibold">Commande</th>
                    <th class="px-4 py-3 font-semibold">Date</th>
                    <th class="px-4 py-3 font-semibold">Articles</th>
                    <th class="px-4 py-3 font-semibold">Montant</th>
                    <th class="px-4 py-3 font-semibold">Paiement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($sales as $order)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-ink">{{ $order->order_number }}</td>
                        <td class="px-4 py-3 text-ink-3">{{ $order->created_at?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-ink-2">{{ $order->items_count }}</td>
                        <td class="px-4 py-3 text-ink">{{ $m($order->total_amount) }} {{ $order->currency }}</td>
                        <td class="px-4 py-3"><x-ui.status-pill :status="$payTone[$order->payment_status] ?? 'processing'" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-ink-3">Aucune vente générée par cet affilié.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- ============ Commissions + Retraits ============ --}}
<div class="mt-8 grid gap-8 lg:grid-cols-2">
    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-ink">Commissions</h2>
        <div class="overflow-hidden rounded-2xl border border-black/10">
            @forelse ($commissions as $c)
                <div class="border-b border-black/[0.06] px-4 py-3 text-sm last:border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-mono text-xs text-ink-3">{{ $c->reference }}</p>
                            <p class="text-ink-2">niv. {{ $c->level }}
                                @if ($c->commission_rate) · {{ rtrim(rtrim(number_format((float) $c->commission_rate * 100, 2, '.', ''), '0'), '.') }} % @endif
                                · {{ $c->created_at?->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink">+ {{ $m($c->amount) }}</span>
                            <x-ui.status-pill :status="$c->status" />
                        </div>
                    </div>
                    {{-- §41 — historique des changements de statut --}}
                    <p class="mt-1.5 text-[11px] text-ink-3">
                        @foreach ($c->statusChanges as $ch)
                            @if (! $loop->first)<span class="text-ink-3/60"> → </span>@endif
                            <span>{{ $ch->to_status }}</span>@if ($ch->created_at) <span class="text-ink-3/60">({{ $ch->created_at->format('d/m') }})</span>@endif
                        @endforeach
                    </p>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-ink-3">Aucune commission.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-ink">Retraits</h2>
        <div class="overflow-hidden rounded-2xl border border-black/10">
            @forelse ($withdrawals as $w)
                <div class="flex items-center justify-between border-b border-black/[0.06] px-4 py-3 text-sm last:border-0">
                    <div>
                        <p class="font-mono text-xs text-ink-3">{{ $w->reference }}</p>
                        <p class="text-ink-2">{{ $w->method }} · {{ $w->requested_at?->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-ink">{{ $m($w->amount) }}</span>
                        <x-ui.status-pill :status="$w->status" />
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-ink-3">Aucun retrait.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
