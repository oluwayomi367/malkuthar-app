@extends('layouts.storefront')

@section('title', 'Conditions du programme d\'affiliation')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
@endphp
<article class="mx-auto w-full max-w-[1280px] px-4 pb-20 pt-12 sm:px-6 sm:pt-16">
<div class="mx-auto max-w-prose">

    <div class="mb-8 rounded-xl border border-amber/30 bg-amber-tint px-4 py-3 text-sm leading-relaxed text-amber" role="note">
        Document de travail — doit être validé par un conseil juridique compétent
        pour chaque pays concerné avant publication (§81).
    </div>

    <h1 class="font-display text-[36px] font-semibold leading-tight text-ink sm:text-[44px]">Conditions du programme d'affiliation MALKUTHAR</h1>

    <p class="mt-4 text-base leading-[1.7] text-ink-2">
        Pour une explication pas à pas du fonctionnement, voir la page
        <a href="{{ route('affiliation') }}" class="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Programme d'affiliation</a>.
    </p>

    <div class="mt-10 space-y-10 border-t border-line pt-10 text-base leading-[1.7] text-ink-2 [&_strong]:font-semibold [&_strong]:text-ink">
        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">1. Fonctionnement</h2>
            <p class="mt-3">
                Tout titulaire d'un compte MALKUTHAR peut activer un espace affilié, sans créer de
                second compte. Il reçoit un identifiant affilié permanent, un code de parrainage et
                un lien personnel de la forme <code class="break-all rounded bg-ground-alt px-1.5 py-0.5 font-mono text-[14px] text-ink">{{ config('app.url') }}/ref/&lt;code&gt;</code>.
                Toute personne arrivant via ce lien est rattachée à l'affilié de façon définitive :
                le parrain ne peut plus être modifié ensuite.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">2. Rémunération — {{ $fmt($rateL1) }} / {{ $fmt($rateL2) }} / {{ $fmt($rateL3) }}</h2>
            <p class="mt-3">
                Les commissions sont versées sur trois niveaux, calculées <strong>indépendamment</strong>
                sur le même montant éligible : {{ $fmt($rateL1) }} % pour l'affilié direct (niveau 1),
                {{ $fmt($rateL2) }} % pour son parrain (niveau 2) et {{ $fmt($rateL3) }} % pour le niveau
                supérieur (niveau 3). Aucune commission au-delà du troisième niveau.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">3. Produits concernés</h2>
            <p class="mt-3">
                Seuls les <strong>produits digitaux explicitement déclarés « affiliables »</strong>
                génèrent des commissions. Les produits physiques et les services n'en génèrent
                <strong>aucune</strong>. Dans une commande mixte, seule la part digitale affiliable
                est prise en compte.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">4. Base de calcul et promotions</h2>
            <p class="mt-3">
                La commission est calculée sur le montant réellement payé pour le produit digital
                affiliable, <strong>après application d'un éventuel coupon de réduction</strong>. Les
                taux applicables et les produits concernés peuvent faire l'objet de campagnes
                promotionnelles distinctes.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">5. Validation des commissions</h2>
            <p class="mt-3">
                Une commission est d'abord « en attente ». Elle devient « disponible » (retirable)
                <strong>{{ $validationDelayDays }} jours</strong> après la vente, sous réserve
                qu'aucun remboursement, litige ou fraude ne soit constaté.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">6. Retraits</h2>
            <p class="mt-3">
                L'affilié demande lui-même ses retraits depuis son espace, dès lors que le solde
                disponible atteint le minimum de <strong>{{ $fmt($minWithdrawal) }}</strong> (unité de
                la devise du compte) et que les contrôles de sécurité sont satisfaits. Des frais de
                retrait peuvent s'appliquer selon le moyen et le pays ; leur montant est affiché
                avant confirmation. Toute modification du moyen de retrait nécessite une vérification
                par code et ouvre un délai de sécurité de {{ $cooldownHours }} heures avant le
                prochain retrait.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">7. Remboursements</h2>
            <p class="mt-3">
                Si une vente est remboursée, la commission correspondante est annulée (si elle était
                encore en attente) ou reprise (si elle était déjà disponible ou payée).
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">8. Fraude et auto-parrainage</h2>
            <p class="mt-3">
                Sont interdits : l'auto-parrainage, la création de comptes multiples ou fictifs, la
                manipulation des liens et toute tentative de génération artificielle de commissions.
                Un affilié ne peut pas être son propre référent sur une commande.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">9. Suspension</h2>
            <p class="mt-3">
                En cas de fraude, d'activité suspecte ou de violation des présentes conditions,
                MALKUTHAR peut suspendre le compte affilié. Les données financières ne sont jamais
                supprimées ; le traitement des commissions en cours et des retraits en cours est
                décidé au cas par cas.
            </p>
        </section>

        <section>
            <h2 class="font-display text-[26px] font-semibold leading-snug text-ink">10. Modification du programme</h2>
            <p class="mt-3">
                MALKUTHAR peut faire évoluer les taux, les délais, les seuils et les conditions du
                programme. Les commissions déjà acquises ne sont pas affectées rétroactivement.
            </p>
        </section>
    </div>
</div>
</article>
@endsection
