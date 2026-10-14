@extends('layouts.storefront')

@section('title', 'Conditions du programme vendeur')

@section('content')
@php
    $commission = rtrim(rtrim(number_format(\App\Support\MarketplaceSettings::defaultCommissionRate() * 100, 2, ',', ' '), '0'), ',');
    $hold = \App\Support\MarketplaceSettings::settlementHoldDays();
    $minPayout = rtrim(rtrim(number_format(\App\Support\MarketplaceSettings::minPayoutAmount(), 2, ',', ' '), '0'), ',');
    $cooldown = \App\Support\MarketplaceSettings::payoutAccountCooldownHours();
@endphp
<article class="mx-auto w-full max-w-[1280px] px-4 pb-20 pt-12 sm:px-6 sm:pt-16">
<div class="mx-auto max-w-prose">
    <div class="mb-8 rounded-xl border border-amber/30 bg-amber-tint px-4 py-3 text-sm leading-relaxed text-amber" role="note">
        Document de travail — à faire valider par un conseil juridique compétent pour chaque pays
        concerné avant publication.
    </div>

    <h1 class="font-display text-[36px] font-semibold leading-tight text-ink sm:text-[44px]">Conditions du programme vendeur MALKUTHAR</h1>

    <div class="mt-10 space-y-10 border-t border-line pt-10 text-base leading-[1.7] text-ink-2 [&_strong]:font-semibold [&_strong]:text-ink">
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">1. Accès</h2>
            <p class="mt-3">
                Tout titulaire d'un compte MALKUTHAR peut demander l'ouverture d'un espace vendeur, sans
                créer de second compte. L'ouverture est soumise à la vérification des justificatifs par
                MALKUTHAR. MALKUTHAR peut refuser ou fermer un compte vendeur.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">2. Offres et validation</h2>
            <p class="mt-3">
                Le vendeur publie des offres (produits physiques, produits digitaux, services,
                formations) dans les catégories ouvertes. <strong>Chaque offre — et chaque modification
                d'une offre en ligne — est validée par un administrateur avant sa mise en ligne.</strong>
                Une offre peut être suspendue à tout moment en cas de manquement.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">3. Commission de la plateforme</h2>
            <p class="mt-3">
                MALKUTHAR prélève une commission sur chaque vente. Le taux par défaut est de
                <strong>{{ $commission }} %</strong> ; il peut varier par catégorie ou par vendeur et
                est connu à l'avance. Le taux appliqué est <strong>figé</strong> sur chaque commande.
                Le reste (net vendeur) est porté au crédit du vendeur.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">4. Règlement et versements</h2>
            <p class="mt-3">
                Le net d'une vente devient disponible <strong>{{ $hold }} jours</strong> après le
                paiement, sous réserve d'absence de remboursement ou de litige. Le vendeur demande ses
                versements depuis son espace dès que le solde disponible atteint
                <strong>{{ $minPayout }}</strong> (devise du compte). Toute modification du moyen de
                versement ouvre un délai de sécurité de {{ $cooldown }} heures.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">5. Remboursements et litiges</h2>
            <p class="mt-3">
                En cas de remboursement d'une commande, le règlement correspondant est repris : la
                somme est déduite du solde du vendeur (report négatif éventuel résorbé par les ventes
                suivantes). Le stock et les places sont rétablis.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">6. Obligations du vendeur</h2>
            <p class="mt-3">
                Le vendeur garantit la conformité, la légalité et la disponibilité de ses offres,
                traite les commandes dans des délais raisonnables et répond aux acheteurs. Sont
                interdits : contrefaçon, produits illicites, fausses informations, contournement de la
                plateforme pour le paiement.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">7. Programme d'affiliation</h2>
            <p class="mt-3">
                La place de marché est <strong>indépendante du programme d'affiliation</strong> à trois
                niveaux : les offres des vendeurs n'y ouvrent pas droit.
            </p>
        </section>
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">8. Évolution du programme</h2>
            <p class="mt-3">
                MALKUTHAR peut faire évoluer les taux, délais, seuils et règles. Les sommes déjà
                acquises ne sont pas affectées rétroactivement.
            </p>
        </section>
    </div>
</div>
</article>
@endsection
