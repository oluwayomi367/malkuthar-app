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
    $available = (float) ($wallet['balance_available'] ?? 0);

    $levelLabel = [1 => 'Vos ventes directes', 2 => 'Ventes de vos filleuls', 3 => 'Niveau suivant'];

    // Dernières commissions (lecture seule, mêmes données que « Mes ventes »).
    $recentCommissions = \App\Models\LedgerEntry::query()
        ->where('user_id', $affiliate->id)
        ->where('source_type', 'commission')
        ->with('orderItem:id,product_name')
        ->latest()
        ->limit(5)
        ->get();
    $commissionStatus = ['pending' => 'processing', 'available' => 'available', 'paid' => 'paid', 'cancelled' => 'cancelled', 'reversed' => 'reversed'];

    $shareText = 'Je vous recommande MALKUTHAR : '.$referralLink;
    $whatsappUrl = 'https://wa.me/?text='.rawurlencode($shareText);
    $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($referralLink);

    $arrow = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
@endphp

<header class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Bonjour {{ $affiliate->first_name ?? 'Affilié' }}</h1>
        <p class="mt-2 text-[15px] text-ink-2">Vos gains, votre lien de parrainage et votre réseau.</p>
    </div>
    @if ($affiliate->affiliate_id)
        <p class="text-sm text-ink-2">Identifiant <span class="ml-1 rounded-full border border-line-strong bg-surface px-2.5 py-1 font-mono text-[13px] text-ink">{{ $affiliate->affiliate_id }}</span></p>
    @endif
</header>

<div id="retrait" class="scroll-mt-24" x-data="withdrawForm(@js($available), @js($currency))">
    <div class="mt-8 grid gap-5 lg:grid-cols-5">
        {{-- Solde --}}
        <section class="flex flex-col rounded-2xl bg-ink p-6 text-white sm:p-7 lg:col-span-3" aria-labelledby="balance-title">
            <h2 id="balance-title" class="font-sans text-sm font-semibold text-accent-light">Solde disponible</h2>
            <p class="mt-3 font-display text-[44px] font-semibold leading-none sm:text-[56px]">
                {{ $money($available) }}<span class="ml-2 font-sans text-base font-medium text-white/70">{{ $currency }}</span>
            </p>

            <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-white/15 pt-5 text-sm">
                <div>
                    <dt class="text-white/70">En validation</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $money($wallet['balance_pending'] ?? 0) }} <span class="text-sm font-normal text-white/70">{{ $currency }}</span></dd>
                </div>
                <div>
                    <dt class="text-white/70">Total gagné</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $money($wallet['total_earned'] ?? 0) }} <span class="text-sm font-normal text-white/70">{{ $currency }}</span></dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="button" x-on:click="toggle()" @disabled($available <= 0) :aria-expanded="open.toString()" aria-controls="withdraw-panel"
                        class="inline-flex min-h-11 items-center rounded-full bg-accent px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-40">
                    Retirer mes gains
                </button>
                @if ($available <= 0)
                    <p class="text-[13px] text-white/70">Le retrait s'ouvre dès qu'un montant est disponible.</p>
                @endif
            </div>
        </section>

        {{-- Lien de parrainage --}}
        <section class="flex flex-col rounded-2xl border border-line bg-surface p-5 sm:p-6 lg:col-span-2" aria-labelledby="referral-title">
            <h2 id="referral-title" class="font-sans text-lg font-semibold text-ink">Votre lien de parrainage</h2>
            <p class="mt-1 text-sm leading-relaxed text-ink-2">Toute inscription ou vente issue de ce lien vous est attribuée. Il est unique et permanent.</p>

            <label for="referral-link" class="sr-only">Lien de parrainage</label>
            <input id="referral-link" type="text" readonly value="{{ $referralLink }}" x-on:focus="$event.target.select()"
                   class="mt-4 min-h-11 w-full rounded-xl border border-line-strong bg-ground px-3.5 font-mono text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">

            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.copy-button :value="$referralLink" label="Copier le lien" />
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-line-strong bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:border-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/></svg>
                    WhatsApp
                </a>
                <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-line-strong bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:border-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/></svg>
                    Facebook
                </a>
            </div>
        </section>
    </div>

    {{-- Panneau retrait --}}
    <section id="withdraw-panel" x-show="open" x-cloak
             class="mt-5 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="withdraw-title">
        <div class="flex items-center justify-between gap-3">
            <h2 id="withdraw-title" class="font-sans text-lg font-semibold text-ink">Demander un retrait</h2>
            <button type="button" x-on:click="open = false" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-ink-2 hover:bg-ground-alt hover:text-ink" aria-label="Fermer le formulaire de retrait">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <x-ui.field name="wd_amount" label="Montant" type="number" x-model.number="amount" :hint="'Disponible : '.$money($available).' '.$currency" />
            <x-ui.select name="wd_method" label="Moyen de retrait" x-model="method"
                :options="['mobile_money' => 'Mobile Money', 'bank_account' => 'Compte bancaire', 'e_wallet' => 'Portefeuille électronique']" />
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2" x-show="method === 'mobile_money'" x-cloak>
            <x-ui.field name="wd_mm_number" label="Numéro Mobile Money" x-model="fields.mobile_money_number" />
            <x-ui.field name="wd_mm_operator" label="Opérateur" x-model="fields.mobile_money_operator" hint="MTN, Moov, Orange…" />
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-3" x-show="method === 'bank_account'" x-cloak>
            <x-ui.field name="wd_bank_name" label="Banque" x-model="fields.bank_name" />
            <x-ui.field name="wd_bank_number" label="N° de compte / IBAN" x-model="fields.bank_account_number" />
            <x-ui.field name="wd_bank_holder" label="Titulaire" x-model="fields.bank_account_holder" />
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2" x-show="method === 'e_wallet'" x-cloak>
            <x-ui.field name="wd_ew_provider" label="Fournisseur" x-model="fields.e_wallet_provider" />
            <x-ui.field name="wd_ew_id" label="Identifiant du portefeuille" x-model="fields.e_wallet_id" />
        </div>

        <p x-show="error" x-cloak role="alert" class="mt-4 text-sm font-medium text-rust" x-text="error"></p>
        <p x-show="success" x-cloak role="status" class="mt-4 text-sm font-medium text-sage" x-text="success"></p>

        <div class="mt-5 flex flex-wrap items-center gap-4 border-t border-line pt-5">
            <button type="button" x-on:click="submit()" :disabled="loading"
                    class="inline-flex min-h-11 items-center rounded-full bg-accent px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-40">
                <span x-show="!loading">Confirmer la demande</span>
                <span x-show="loading" x-cloak>Traitement…</span>
            </button>
            <p class="text-[13px] text-ink-3">
                Besoin de changer de numéro ? Passez par
                <a href="{{ route('affiliate.withdrawal-method.store') }}" class="font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Moyen de paiement</a>
                (vérification par code).
            </p>
        </div>
    </section>
</div>

{{-- Réseau --}}
<section class="mt-12" aria-labelledby="network-title">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <h2 id="network-title" class="font-display text-[28px] font-semibold text-ink">Mon réseau sur trois niveaux</h2>
        <a href="{{ route('affiliate.lineage') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">Voir le détail {!! $arrow !!}</a>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        @foreach (($team ?? []) as $level)
            <article class="rounded-2xl border border-line bg-surface p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-ink">Niveau {{ $level['level'] }}</h3>
                        <p class="text-[13px] text-ink-3">{{ $levelLabel[$level['level']] ?? '' }}</p>
                    </div>
                    <p class="font-display text-[32px] font-semibold leading-none text-accent-2">{{ $level['rate'] }}&nbsp;%</p>
                </div>
                <dl class="mt-5 space-y-2 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-2">Filleuls</dt><dd class="font-semibold text-ink">{{ $level['members'] ?? 0 }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-2">Actifs</dt><dd class="font-semibold text-ink">{{ $level['active'] ?? 0 }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-2">Ventes</dt><dd class="font-semibold text-ink">{{ $level['sales'] ?? 0 }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-2">CA généré</dt><dd class="font-semibold text-ink">{{ $money($level['revenue'] ?? 0) }} {{ $currency }}</dd></div>
                </dl>
            </article>
        @endforeach
    </div>
    <p class="mt-3 text-[13px] text-ink-3">Seuls des totaux sont affichés : les données personnelles de votre réseau restent privées.</p>
</section>

{{-- Dernières commissions --}}
<section class="mt-12" aria-labelledby="commissions-title">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <h2 id="commissions-title" class="font-display text-[28px] font-semibold text-ink">Dernières commissions</h2>
        <a href="{{ route('affiliate.sales') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">Toutes mes ventes {!! $arrow !!}</a>
    </div>

    @if ($recentCommissions->isEmpty())
        <x-ui.empty-state class="mt-4" icon="wallet" title="Aucune commission pour le moment" text="Partagez votre lien : chaque vente éligible issue de votre réseau apparaîtra ici." />
    @else
        <ul class="mt-4 divide-y divide-line rounded-2xl border border-line bg-surface">
            @foreach ($recentCommissions as $c)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-[15px] font-medium text-ink">{{ $c->orderItem?->product_name ?? $c->description }}</p>
                        <p class="mt-0.5 text-[13px] text-ink-3">
                            {{ $c->created_at->format('d/m/Y') }} · Niveau {{ $c->level }} · <span class="font-mono">{{ $c->reference }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.status-pill :status="$commissionStatus[$c->status] ?? 'processing'" />
                        <p class="whitespace-nowrap text-[15px] font-semibold text-ink">+{{ $money($c->amount) }} {{ $c->currency }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
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
            init() {
                // Le menu « Retraits » pointe vers #retrait : on ouvre directement le formulaire.
                if (window.location.hash === '#retrait' && max > 0) { this.open = true; }
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
