@extends('layouts.storefront')

@section('title', "Programme d'affiliation")
@section('seo_description', "Comment fonctionne le programme d'affiliation MALKUTHAR : trois niveaux de commission ({$rateL1} / {$rateL2} / {$rateL3} %) sur les produits digitaux éligibles, du lien de parrainage au versement.")

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');

    $etapes = [
        ['Activez votre espace', "Depuis votre compte MALKUTHAR, activez l'espace affilié en un clic. Aucun second compte : votre compte client devient aussi votre compte affilié. Vous recevez un identifiant permanent et un lien de parrainage personnel."],
        ['Partagez votre lien', "Diffusez votre lien <code>".e(config('app.url'))."/{$referralPath}/&lt;code&gt;</code> où vous voulez : réseaux, messagerie, site. Toute personne qui arrive par ce lien vous est rattachée pendant {$referralCookieDays} jours, puis de façon définitive dès qu'elle crée son compte."],
        ['La vente est attribuée', "Quand une personne rattachée achète un produit digital éligible, la commande est reliée à vous. L'attribution du parrain est immuable : elle ne peut plus changer ensuite."],
        ['La commission est créditée', "Dès le paiement confirmé, les commissions des trois niveaux sont inscrites dans votre registre au statut « en attente »."],
    ];

    $suivi = [
        ['Mes ventes', 'Chaque commande référée : produit, date, montant éligible, niveau, commission et statut.'],
        ['Ma lignée', "Vos filleuls sur trois niveaux : nombre, actifs, ventes réalisées et chiffre d'affaires généré."],
        ['Mes gains', 'Solde disponible, total des commissions et total retiré — reconstitués depuis le registre, pas une simple variable.'],
    ];

    $regles = [
        "L'auto-parrainage est interdit : vous ne pouvez pas être votre propre référent sur une commande.",
        'Les comptes multiples ou fictifs et la manipulation des liens entraînent la suspension.',
        'Le parrain est définitif dès le rattachement — ni vous ni le filleul ne pouvez le changer.',
        "En cas de fraude ou de violation des conditions, l'espace affilié peut être suspendu. Aucune donnée financière n'est supprimée.",
    ];
@endphp

{{-- ================= HERO ================= --}}
<section class="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Programme d'affiliation</p>
    <h1 class="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
        Recommandez, et gagnez sur trois niveaux.
    </h1>
    <p class="mt-4 max-w-xl text-ink-2">
        Partagez les produits digitaux MALKUTHAR autour de vous. Chaque vente issue de votre lien
        vous rémunère — ainsi que les deux parrains au-dessus de vous. Voici, en détail, comment
        le système fonctionne, du premier clic jusqu'au versement.
    </p>
    <div class="mt-6 flex flex-wrap gap-3">
        <x-ui.button variant="primary" :href="Auth::check() ? route('affiliate.join') : route('register')">
            {{ Auth::check() ? 'Activer mon espace affilié' : 'Créer un compte' }}
        </x-ui.button>
        <x-ui.button variant="ghost" :href="route('affiliate.program-terms')">Lire les conditions →</x-ui.button>
    </div>
</section>

{{-- ================= EN BREF ================= --}}
<section class="border-y border-line bg-ground-alt">
    <div class="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-2 lg:grid-cols-4">
        @php
            $chiffres = [
                ['Niveaux rémunérés', $paidLevels, "Aucune commission au-delà du {$paidLevels}ᵉ niveau."],
                ['Taux par niveau', "{$fmt($rateL1)} · {$fmt($rateL2)} · {$fmt($rateL3)} %", 'Calculés indépendamment sur le même montant éligible.'],
                ['Validation', "{$validationDelayDays} jours", 'Délai avant qu\'une commission devienne retirable.'],
                ['Retrait minimum', $fmt($minWithdrawal), 'Dans la devise de votre compte.'],
            ];
        @endphp
        @foreach ($chiffres as [$label, $value, $foot])
            <div class="bg-ground-alt px-4 py-8 sm:px-6">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">{{ $label }}</p>
                <p class="mt-2 font-display text-2xl font-semibold tracking-[-0.01em] text-ink">{{ $value }}</p>
                <p class="mt-2 text-[13px] leading-snug text-ink-2">{{ $foot }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= COMMENT ÇA MARCHE ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
    <x-ui.section-heading eyebrow="Le parcours" title="Comment ça marche" />
    <ol class="grid gap-6 sm:grid-cols-2">
        @foreach ($etapes as $i => [$titre, $texte])
            <li class="flex gap-4">
                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-sm font-semibold text-accent">
                    {{ $i + 1 }}
                </span>
                <div>
                    <p class="font-display text-[15px] font-semibold text-ink">{{ $titre }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-2">{!! $texte !!}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

{{-- ================= LES 3 NIVEAUX ================= --}}
<section class="border-t border-line bg-ground-alt">
    <div class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
        <x-ui.section-heading eyebrow="La rémunération" title="Trois niveaux, calculés séparément" />
        <p class="max-w-2xl text-sm text-ink-2">
            Pour chaque vente éligible, trois commissions sont générées en parallèle, chacune
            calculée <strong class="text-ink">sur le même montant éligible</strong> — jamais en
            cascade les unes sur les autres. Au total, {{ $fmt($rateTotal) }} % du montant éligible
            est distribué.
        </p>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            @php
                $niveaux = [
                    ['Niveau 1', 'Vos ventes directes', $rateL1, 'La personne que vous avez parrainée achète.'],
                    ['Niveau 2', 'Les ventes de vos filleuls', $rateL2, 'Un filleul de votre filleul achète.'],
                    ['Niveau 3', 'Le niveau suivant', $rateL3, "Encore un cran en dessous — puis plus rien."],
                ];
            @endphp
            @foreach ($niveaux as [$niv, $sous, $taux, $ex])
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(46,44,40,0.04)]">
                    <div class="flex items-baseline justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">{{ $niv }}</p>
                        <p class="font-display text-2xl font-bold text-accent">{{ $fmt($taux) }} %</p>
                    </div>
                    <p class="mt-2 text-[15px] font-semibold text-ink">{{ $sous }}</p>
                    <p class="mt-1 text-[13px] leading-snug text-ink-2">{{ $ex }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-xl border border-line bg-surface px-4 py-3 text-[13px] text-ink-2">
            <span class="font-semibold text-ink">Exemple —</span>
            sur un produit éligible payé <strong>10 000</strong> (net de coupon), l'affilié direct
            reçoit {{ $fmt($rateL1 * 100) }}, son parrain {{ $fmt($rateL2 * 100) }} et le niveau
            au-dessus {{ $fmt($rateL3 * 100) }}.
        </div>
    </div>
</section>

{{-- ================= PRODUITS CONCERNÉS ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
    <x-ui.section-heading eyebrow="Le périmètre" title="Ce qui génère une commission" />
    <div class="grid gap-6 sm:grid-cols-2">
        <div class="rounded-2xl border border-brand-green/25 bg-brand-green/[0.06] p-5">
            <p class="font-display text-[15px] font-semibold text-ink">Produits digitaux éligibles</p>
            <p class="mt-1 text-sm leading-relaxed text-ink-2">
                Seuls les produits digitaux explicitement déclarés « affiliables » rémunèrent.
                Ils portent une mention visible sur leur fiche.
            </p>
        </div>
        <div class="rounded-2xl border border-line bg-surface p-5">
            <p class="font-display text-[15px] font-semibold text-ink">Physiques &amp; services</p>
            <p class="mt-1 text-sm leading-relaxed text-ink-2">
                Les produits physiques et les prestations de service ne génèrent
                <strong class="text-ink">aucune</strong> commission. Dans une commande mixte,
                seule la part digitale éligible est prise en compte.
            </p>
        </div>
    </div>
    <p class="mt-5 text-sm text-ink-2">
        La commission est calculée sur le montant <strong class="text-ink">réellement payé</strong>
        pour le produit éligible, <strong class="text-ink">après application d'un éventuel coupon</strong>.
        Les taux et les produits concernés peuvent faire l'objet de campagnes promotionnelles.
    </p>
</section>

{{-- ================= DU CLIC AU VERSEMENT ================= --}}
<section class="border-t border-line bg-ground-alt">
    <div class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
        <x-ui.section-heading eyebrow="Le cycle de vie" title="Du clic au versement" />
        <ol class="space-y-4">
            @php
                $cycle = [
                    ['En attente', "La commission est créée dès le paiement. Elle n'est pas encore retirable."],
                    ['Disponible', "Après {$validationDelayDays} jours sans remboursement, litige ni fraude, elle devient retirable."],
                    ['Demande de retrait', "Vous déclenchez vous-même le retrait depuis votre espace, dès que le solde disponible atteint {$fmt($minWithdrawal)}."],
                    ['Contrôles de sécurité', "Vérification par code si besoin. Toute modification du moyen de retrait ouvre un délai de {$cooldownHours} h avant le prochain retrait. Des frais peuvent s'appliquer, affichés avant confirmation."],
                    ['Versement', 'Le paiement est envoyé sur le moyen de retrait choisi et tracé dans votre registre.'],
                ];
            @endphp
            @foreach ($cycle as $i => [$etat, $desc])
                <li class="flex gap-4 rounded-xl border border-line bg-surface px-4 py-3.5">
                    <span class="mt-0.5 font-display text-sm font-semibold text-ink-3">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <p class="text-[14px] font-semibold text-ink">{{ $etat }}</p>
                        <p class="mt-0.5 text-[13px] leading-snug text-ink-2">{{ $desc }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="mt-5 text-sm text-ink-2">
            <strong class="text-ink">En cas de remboursement</strong> d'une vente, la commission
            correspondante est annulée si elle était encore en attente, ou reprise si elle était
            déjà disponible ou payée.
        </p>
    </div>
</section>

{{-- ================= SUIVI ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
    <x-ui.section-heading eyebrow="La transparence" title="Tout est suivi dans votre espace" />
    <div class="grid gap-8 sm:grid-cols-3">
        @foreach ($suivi as [$titre, $texte])
            <div>
                <p class="text-[15px] font-semibold text-ink">{{ $titre }}</p>
                <p class="mt-1 text-sm text-ink-2">{{ $texte }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= RÈGLES ================= --}}
<section class="border-t border-line bg-ground-alt">
    <div class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
        <x-ui.section-heading eyebrow="Les règles" title="Ce qui est interdit" />
        <ul class="space-y-3">
            @foreach ($regles as $regle)
                <li class="flex gap-3 text-sm text-ink-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-rust" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round"/>
                    </svg>
                    <span>{{ $regle }}</span>
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-sm text-ink-2">
            Le détail contractuel complet figure dans les
            <a href="{{ route('affiliate.program-terms') }}" class="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Conditions du programme d'affiliation</a>.
            Cette page-ci en explique le fonctionnement ; les Conditions font foi.
        </p>
    </div>
</section>

{{-- ================= CTA FINAL ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-14 text-center sm:px-6">
    <h2 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink">Prêt à recommander MALKUTHAR ?</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-ink-2">
        L'activation est gratuite et immédiate. Vous gardez le même compte.
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
        <x-ui.button variant="primary" :href="Auth::check() ? route('affiliate.join') : route('register')">
            {{ Auth::check() ? 'Activer mon espace affilié' : 'Créer un compte' }}
        </x-ui.button>
        <x-ui.button variant="secondary" :href="route('catalog.index', ['type' => 'digital'])">Voir les produits digitaux</x-ui.button>
    </div>
</section>
@endsection
