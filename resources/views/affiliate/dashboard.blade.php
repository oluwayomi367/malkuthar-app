{{--
    Variables (AffiliateDashboardController@index) :
    $affiliate  App\Models\User
    $wallet     array ['balance_available', 'balance_pending', 'total_earned', 'currency']
    $referralLink  string
    $team       array<int, ['level', 'members', 'active', 'revenue', 'rate']>
--}}
@extends('layouts.app')

@section('title', 'Espace affilié')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $currency = $wallet['currency'] ?? 'FCFA';
    $money = fn ($n) => number_format((float) ($n ?? 0), 0, ',', ' ');
    $levelAccent = [1 => 'text-accent', 2 => 'text-emerald-300', 3 => 'text-amber'];
    $available = (float) ($wallet['balance_available'] ?? 0);
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace affilié</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Bonjour {{ $affiliate->first_name ?? 'Affilié' }}</h1>
    </div>
    <span class="rounded-full border border-black/10 bg-black/[0.04] px-3 py-1 font-mono text-xs text-ink-2">{{ $affiliate->affiliate_id }}</span>
</div>

<div x-data="withdrawForm(@js($available), @js($currency))">
    {{-- Soldes --}}
    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <x-ui.stat-tile label="Solde disponible" :value="$money($available)" :currency="$currency" :emphasis="true">
            <x-slot:action>
                <button type="button" x-on:click="toggle()" @disabled($available <= 0)
                        class="inline-flex h-9 items-center rounded-lg bg-accent px-3.5 text-sm font-semibold text-white shadow-[0_8px_24px_-8px] shadow-black/10 disabled:opacity-40">
                    Retirer mes gains
                </button>
            </x-slot:action>
        </x-ui.stat-tile>
        <x-ui.stat-tile label="Commissions en attente" :value="$money($wallet['balance_pending'] ?? 0)" :currency="$currency" />
        <x-ui.stat-tile label="Total gagné" :value="$money($wallet['total_earned'] ?? 0)" :currency="$currency" />
    </div>

    {{-- Panneau retrait --}}
    <div x-show="open" x-cloak x-transition.origin.top
         class="mt-3 rounded-2xl border border-black/10 bg-surface p-5">
        <div class="flex items-center justify-between">
            <p class="font-display font-semibold text-ink">Demander un retrait</p>
            <button type="button" x-on:click="open = false" class="text-ink-3 hover:text-ink" aria-label="Fermer">✕</button>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.field name="wd_amount" label="Montant" type="number" x-model.number="amount" :hint="'Disponible : '.$money($available).' '.$currency" />
            <x-ui.select name="wd_method" label="Moyen de retrait" x-model="method"
                :options="['mobile_money' => 'Mobile Money', 'bank_account' => 'Compte bancaire', 'e_wallet' => 'Portefeuille électronique']" />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="method === 'mobile_money'" x-cloak>
            <x-ui.field name="wd_mm_number" label="Numéro Mobile Money" x-model="fields.mobile_money_number" />
            <x-ui.field name="wd_mm_operator" label="Opérateur" x-model="fields.mobile_money_operator" hint="MTN, Moov, Orange…" />
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3" x-show="method === 'bank_account'" x-cloak>
            <x-ui.field name="wd_bank_name" label="Banque" x-model="fields.bank_name" />
            <x-ui.field name="wd_bank_number" label="N° de compte / IBAN" x-model="fields.bank_account_number" />
            <x-ui.field name="wd_bank_holder" label="Titulaire" x-model="fields.bank_account_holder" />
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="method === 'e_wallet'" x-cloak>
            <x-ui.field name="wd_ew_provider" label="Fournisseur" x-model="fields.e_wallet_provider" />
            <x-ui.field name="wd_ew_id" label="Identifiant du portefeuille" x-model="fields.e_wallet_id" />
        </div>

        <p x-show="error" x-cloak class="mt-3 text-sm text-rust" x-text="error"></p>
        <p x-show="success" x-cloak class="mt-3 text-sm text-emerald-300" x-text="success"></p>

        <div class="mt-4">
            <button type="button" x-on:click="submit()" :disabled="loading"
                    class="inline-flex h-11 items-center rounded-xl bg-accent px-5 text-sm font-semibold text-white shadow-[0_10px_34px_-10px] shadow-black/10 disabled:opacity-40 sm:h-10">
                <span x-show="!loading">Confirmer la demande</span>
                <span x-show="loading" x-cloak>Traitement…</span>
            </button>
        </div>
        <p class="mt-3 text-xs text-ink-3">
            Besoin de changer de numéro ? Passez par
            <a href="{{ route('affiliate.withdrawal-method.store') }}" class="text-accent hover:underline">Moyen de paiement</a>
            (vérification par code, §56).
        </p>
    </div>
</div>

{{-- Lien d'affiliation --}}
<div class="mt-8 rounded-2xl border border-black/10 bg-surface p-5">
    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Mon lien d'affiliation</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <code class="min-w-0 flex-1 truncate rounded-lg border border-black/10 bg-ground/60 px-3 py-2.5 font-mono text-sm text-ink">{{ $referralLink }}</code>
        <x-ui.copy-button :value="$referralLink" label="Copier le lien" />
    </div>
    <p class="mt-2 text-xs text-ink-3">Lien unique et permanent (§33). Toute inscription ou vente issue de ce lien vous est attribuée.</p>
</div>

{{-- Équipe / lignée --}}
<div class="mt-8">
    <h2 class="mb-3 font-display text-lg font-semibold text-ink">Mon équipe</h2>
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach (($team ?? []) as $level)
            <div class="rounded-2xl border border-black/10 bg-surface p-4">
                <div class="flex items-center justify-between">
                    <p class="font-display text-sm font-semibold text-ink">Niveau {{ $level['level'] }}</p>
                    <span class="rounded-full border border-black/10 bg-black/[0.04] px-2 py-0.5 text-xs font-semibold {{ $levelAccent[$level['level']] ?? 'text-ink-2' }}">{{ $level['rate'] }} %</span>
                </div>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-3">Filleuls</dt><dd class="text-ink">{{ $level['members'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-3">Actifs</dt><dd class="text-ink">{{ $level['active'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-3">Ventes</dt><dd class="text-ink">{{ $level['sales'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-3">CA généré</dt><dd class="text-ink">{{ $money($level['revenue'] ?? 0) }} {{ $currency }}</dd></div>
                </dl>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-ink-3">Seuls des agrégats sont affichés : les données personnelles de votre réseau restent privées (§46).</p>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('withdrawForm', (max, currency) => ({
            open: false,
            loading: false,
            error: '',
            success: '',
            amount: max,
            method: 'mobile_money',
            fields: {
                mobile_money_number: '', mobile_money_operator: '',
                bank_name: '', bank_account_number: '', bank_account_holder: '',
                e_wallet_provider: '', e_wallet_id: '',
            },
            toggle() {
                this.open = ! this.open;
                if (this.open) { this.error = ''; this.success = ''; }
            },
            async submit() {
                this.loading = true; this.error = ''; this.success = '';
                try {
                    const res = await fetch(@js(route('affiliate.withdrawals.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        },
                        body: JSON.stringify({ amount: this.amount, method: this.method, ...this.fields }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok) {
                        this.error = data.message || 'La demande a été refusée.';
                    } else {
                        this.success = (data.message || 'Demande enregistrée.') + ' Actualisation…';
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } catch (e) {
                    this.error = 'Erreur réseau. Réessayez.';
                } finally {
                    this.loading = false;
                }
            },
        }));
    });
</script>
@endpush
