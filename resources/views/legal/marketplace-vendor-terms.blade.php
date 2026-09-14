@extends('layouts.storefront')

@section('title', 'Conditions du programme vendeur')

@section('content')
@php
    $commission = rtrim(rtrim(number_format(\App\Support\MarketplaceSettings::defaultCommissionRate() * 100, 2, ',', ' '), '0'), ',');
    $hold = \App\Support\MarketplaceSettings::settlementHoldDays();
    $minPayout = rtrim(rtrim(number_format(\App\Support\MarketplaceSettings::minPayoutAmount(), 2, ',', ' '), '0'), ',');
    $cooldown = \App\Support\MarketplaceSettings::payoutAccountCooldownHours();
@endphp
<div class="mx-auto w-full max-w-2xl px-4 pb-20 pt-10 sm:px-6">
    <div class="mb-6 rounded-xl border border-amber/40 bg-amber/10 p-3 text-xs text-amber">
        Document de travail — à faire valider par un conseil juridique compétent pour chaque pays
        concerné avant publication.
    </div>

    <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Conditions du programme vendeur MALKUTHAR</h1>

    <div class="mt-6 space-y-6 text-sm leading-relaxed text-ink-2">
        <section>
            <h2 class="font-display text-base font-semibold text-ink">1. Accès</h2>
            <p class="mt-1">
                Tout titulaire d'un compte MALKUTHAR peut demander l'ouverture d'un espace vendeur, sans
                créer de second compte. L'ouverture est soumise à la vérification des justificatifs par
                MALKUTHAR. MALKUTHAR peut refuser ou fermer un compte vendeur.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">2. Offres et validation</h2>
            <p class="mt-1">
                Le vendeur publie des offres (produits physiques, produits digitaux, services,
                formations) dans les catégories ouvertes. <strong>Chaque offre — et chaque modification
                d'une offre en ligne — est validée par un administrateur avant sa mise en ligne.</strong>
                Une offre peut être suspendue à tout moment en cas de manquement.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">3. Commission de la plateforme</h2>
            <p class="mt-1">
                MALKUTHAR prélève une commission sur chaque vente. Le taux par défaut est de
                <strong>{{ $commission }} %</strong> ; il peut varier par catégorie ou par vendeur et
                est connu à l'avance. Le taux appliqué est <strong>figé</strong> sur chaque commande.
                Le reste (net vendeur) est porté au crédit du vendeur.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">4. Règlement et versements</h2>
            <p class="mt-1">
                Le net d'une vente devient disponible <strong>{{ $hold }} jours</strong> après le
                paiement, sous réserve d'absence de remboursement ou de litige. Le vendeur demande ses
                versements depuis son espace dès que le solde disponible atteint
                <strong>{{ $minPayout }}</strong> (devise du compte). Toute modification du moyen de
                versement ouvre un délai de sécurité de {{ $cooldown }} heures.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">5. Remboursements et litiges</h2>
            <p class="mt-1">
                En cas de remboursement d'une commande, le règlement correspondant est repris : la
                somme est déduite du solde du vendeur (report négatif éventuel résorbé par les ventes
                suivantes). Le stock et les places sont rétablis.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">6. Obligations du vendeur</h2>
            <p class="mt-1">
                Le vendeur garantit la conformité, la légalité et la disponibilité de ses offres,
                traite les commandes dans des délais raisonnables et répond aux acheteurs. Sont
                interdits : contrefaçon, produits illicites, fausses informations, contournement de la
                plateforme pour le paiement.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">7. Programme d'affiliation</h2>
            <p class="mt-1">
                La place de marché est <strong>indépendante du programme d'affiliation</strong> à trois
                niveaux : les offres des vendeurs n'y ouvrent pas droit.
            </p>
        </section>
        <section>
            <h2 class="font-display text-base font-semibold text-ink">8. Évolution du programme</h2>
            <p class="mt-1">
                MALKUTHAR peut faire évoluer les taux, délais, seuils et règles. Les sommes déjà
                acquises ne sont pas affectées rétroactivement.
            </p>
        </section>
    </div>
</div>
@endsection
