{{--
    Variables attendues (à fournir par le contrôleur, ex: AffiliateDashboardController@index) :

    $affiliate      App\Models\User   — l'affilié connecté
    $wallet         array|object      — ['balance_available' => float, 'balance_pending' => float,
                                          'total_earned' => float, 'currency' => string]
    $referralLink   string            — ex: https://malkuthar.com/ref/JEAN125
    $team           array             — 3 entrées : [
                                            ['level' => 1, 'members' => int, 'active' => int,
                                             'revenue' => float, 'rate' => 15],
                                            ... niveau 2 (rate 10), niveau 3 (rate 5)
                                          ]

    Les valeurs par défaut ci-dessous sont des zéros de sécurité, pas des
    données de démonstration — si elles s'affichent, c'est que le
    contrôleur ne passe pas encore les bonnes variables.
--}}
@extends('layouts.affiliate')

@section('title', 'Mon espace affilié')

@section('content')
    @php
        $currency = $wallet['currency'] ?? 'FCFA';
        $formatMoney = fn ($amount) => number_format((float) ($amount ?? 0), 0, ',', ' ');

        $levelColors = [
            1 => ['dot' => 'bg-brand-gold', 'chip' => 'bg-brand-gold/15 text-brand-gold'],
            2 => ['dot' => 'bg-brand-green', 'chip' => 'bg-brand-green/15 text-brand-green'],
            3 => ['dot' => 'bg-brand-violet', 'chip' => 'bg-brand-violet/15 text-brand-violet'],
        ];
    @endphp

    <div class="mx-auto w-full max-w-md px-4 pb-12 pt-5">

        {{-- Barre supérieure --}}
        <header class="mb-6 flex items-center justify-between">
            <div>
                <p class="font-display text-lg font-semibold tracking-tight text-paper">MALKUTHAR</p>
                <p class="text-sm text-ink-soft">Bonjour {{ $affiliate->first_name ?? 'Affilié' }}</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface font-display text-sm font-semibold text-paper">
                {{ strtoupper(substr($affiliate->first_name ?? 'M', 0, 1)) }}
            </div>
        </header>

        {{-- ============================================================
             BLOC 1 — Solde disponible (carte "ticket", claire sur fond sombre)
        ============================================================ --}}
        <section class="relative overflow-hidden rounded-2xl bg-paper px-5 pb-6 pt-5 text-ink shadow-[0_1px_0_theme(colors.surface-line)]">
            <p class="text-sm text-ink/60">Solde disponible</p>
            <p class="mt-1 font-display text-4xl font-semibold tracking-tight">
                {{ $formatMoney($wallet['balance_available'] ?? 0) }}
                <span class="text-xl font-medium text-ink/50">{{ $currency }}</span>
            </p>

            <button
                type="button"
                x-data
                onclick="alert('Ouvrir le flux de retrait Mobile Money')"
                class="mt-4 w-full rounded-xl bg-ink py-3 text-center font-display text-sm font-semibold text-paper transition active:scale-[0.98]"
            >
                Retirer mes gains
            </button>

            {{-- Bord perforé façon ticket --}}
            <div
                class="absolute inset-x-0 bottom-0 h-3"
                style="background-image: radial-gradient(circle at 6px 0, theme(colors.ink) 3px, transparent 3px); background-size: 12px 6px; background-position: bottom;"
                aria-hidden="true"
            ></div>
        </section>

        {{-- ============================================================
             BLOC 2 & 3 — Commissions en attente / Total gagné
        ============================================================ --}}
        <section class="mt-3 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-surface p-4">
                <p class="text-sm text-ink-soft">Commissions en attente</p>
                <p class="mt-1 font-display text-xl font-semibold text-brand-violet">
                    {{ $formatMoney($wallet['balance_pending'] ?? 0) }}
                    <span class="text-sm font-medium text-ink-soft">{{ $currency }}</span>
                </p>
            </div>
            <div class="rounded-2xl bg-surface p-4">
                <p class="text-sm text-ink-soft">Total gagné</p>
                <p class="mt-1 font-display text-xl font-semibold text-brand-green">
                    {{ $formatMoney($wallet['total_earned'] ?? 0) }}
                    <span class="text-sm font-medium text-ink-soft">{{ $currency }}</span>
                </p>
            </div>
        </section>

        {{-- ============================================================
             BLOC 4 — Lien d'affiliation + bouton copier
        ============================================================ --}}
        <section
            x-data="{
                link: @js($referralLink ?? ''),
                copied: false,
                copy() {
                    navigator.clipboard.writeText(this.link).then(() => {
                        this.copied = true;
                        setTimeout(() => (this.copied = false), 2000);
                    });
                },
            }"
            class="mt-3 rounded-2xl border border-surface-line bg-surface p-4"
        >
            <p class="text-sm text-ink-soft">Mon lien d'affiliation</p>
            <p class="mt-1 truncate font-display text-base font-medium text-paper" x-text="link"></p>

            <button
                type="button"
                @click="copy()"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-surface-line py-2.5 text-sm font-semibold text-paper transition active:scale-[0.98]"
                :class="copied ? 'border-brand-green text-brand-green' : ''"
            >
                <span x-show="!copied">Copier le lien</span>
                <span x-show="copied" x-cloak>Lien copié</span>
            </button>
        </section>

        {{-- ============================================================
             BLOC 5 — Arbre d'équipe sur 3 niveaux (élément signature)
        ============================================================ --}}
        <section class="mt-6">
            <p class="mb-3 font-display text-base font-semibold text-paper">Mon équipe</p>

            <ol class="relative">
                @foreach (($team ?? []) as $i => $level)
                    @php $colors = $levelColors[$level['level']] ?? $levelColors[1]; @endphp
                    <li
                        class="relative border-l-2 border-surface-line pb-6 pl-6 last:border-transparent last:pb-0"
                        style="margin-left: {{ $i * 18 }}px"
                    >
                        <span class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-2 border-ink {{ $colors['dot'] }}"></span>

                        <div class="flex items-center gap-2">
                            <p class="font-display text-sm font-semibold text-paper">Niveau {{ $level['level'] }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $colors['chip'] }}">
                                {{ $level['rate'] }}%
                            </span>
                        </div>

                        <p class="mt-0.5 text-sm text-ink-soft">
                            {{ $level['members'] ?? 0 }} filleuls · {{ $level['active'] ?? 0 }} actifs
                        </p>
                        <p class="text-sm text-ink-soft">
                            {{ $formatMoney($level['revenue'] ?? 0) }} {{ $currency }} générés
                        </p>
                    </li>
                @endforeach
            </ol>
        </section>

    </div>
@endsection
