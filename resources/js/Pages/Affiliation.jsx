import { Head, Link, usePage } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import EmptyState from '../Components/ui/EmptyState';
import Price from '../Components/commerce/Price';
import { money } from '../lib/format';

const fmt = (n) => {
    const s = Number(n).toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return s.replace('.', ',');
};

export default function Affiliation({
    rateL1, rateL2, rateL3, rateTotal, paidLevels, validationDelayDays,
    referralCookieDays, referralPath, minWithdrawal, cooldownHours, showcase,
}) {
    const { auth } = usePage().props;
    const joinHref = auth?.user ? route('affiliate.join') : route('register.affiliate');

    const chiffres = [
        { label: 'Niveau 1 (Direct)', value: `${fmt(rateL1)} %`, desc: 'Sur vos recommandations personnelles' },
        { label: 'Niveau 2 (Filleuls)', value: `${fmt(rateL2)} %`, desc: 'Sur les ventes de vos filleuls' },
        { label: 'Niveau 3 (Lignée)', value: `${fmt(rateL3)} %`, desc: 'Sur les ventes du 3ᵉ niveau' },
        { label: 'Retrait minimum', value: money(minWithdrawal), desc: `Validation sous ${validationDelayDays} jours` },
    ];

    const etapes = [
        {
            num: '01',
            titre: 'Activez votre lien',
            texte: `En 1 clic depuis votre compte. Votre lien unique reste actif et mémorise vos filleuls pendant ${referralCookieDays} jours.`,
            icon: (
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            ),
        },
        {
            num: '02',
            titre: 'Partagez & Inspirez',
            texte: 'Diffusez vos coups de cœur sur vos réseaux sociaux, WhatsApp, chaîne ou blog. Tout acheteur vous est rattaché.',
            icon: (
                <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            ),
        },
        {
            num: '03',
            titre: 'Encaissez vos gains',
            texte: 'Dès paiement confirmé, vos commissions sont inscrites et retirables en toute sécurité par Mobile Money ou virement.',
            icon: (
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            ),
        },
    ];

    return (
        <>
            <Head title="Programme d'affiliation — MALKUTHAR">
                <meta
                    name="description"
                    content={`Gagnez des commissions sur trois niveaux (${rateL1} / ${rateL2} / ${rateL3} %) en recommandant les créations et formations digitales MALKUTHAR.`}
                />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 py-6 sm:px-6 sm:py-8 space-y-12">
                {/* 1. HERO BANNER VISUEL — CARTE ÉPURÉE AVEC PHOTO */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                        <div className="flex flex-col justify-center">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                Programme Partenaires & Affiliation
                            </span>
                            <h1 className="mt-3 sm:mt-4 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl lg:text-4xl leading-[1.15]">
                                Recommandez l'excellence,
                                <br />
                                gagnez sur 3 niveaux de ventes
                            </h1>
                            <p className="mt-3 max-w-xl text-xs sm:text-sm leading-relaxed text-ink-2">
                                Partagez les formations, guides et créations digitales d'exception certifiés par la maison MALKUTHAR.
                                Touchez jusqu'à <strong className="text-ink font-semibold">{fmt(rateTotal)} %</strong> de commissions réparties sur vous et votre lignée.
                            </p>
                            <div className="mt-6 flex flex-wrap items-center gap-3">
                                <Button variant="primary" href={joinHref} native className="h-10 px-5 text-xs sm:text-sm font-semibold shadow-xs">
                                    {auth?.user ? 'Activer mon espace affilié' : 'Rejoindre le réseau'}
                                </Button>
                                <Button variant="secondary" href="#catalogue" native className="h-10 px-4 text-xs sm:text-sm font-medium">
                                    Voir les produits affiliables →
                                </Button>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src="/images/affiliation-hero.jpg"
                                alt="Partenaire affilié MALKUTHAR"
                                className="h-52 sm:h-64 md:h-72 lg:h-80 w-full object-cover object-center"
                            />
                        </div>
                    </div>
                </section>

                {/* 2. STATS & CHIFFRES CLÉS EN CARTES VISUELLES */}
                <section className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    {chiffres.map((c) => (
                        <div key={c.label} className="rounded-2xl border border-line-strong/50 bg-surface p-4 sm:p-5 shadow-2xs">
                            <span className="text-[10.5px] sm:text-xs font-semibold uppercase tracking-wider text-ink-3">
                                {c.label}
                            </span>
                            <p className="mt-1.5 font-display text-xl sm:text-2xl lg:text-3xl font-bold tracking-tight text-accent">
                                {c.value}
                            </p>
                            <p className="mt-1 text-[11px] sm:text-xs text-ink-2 leading-tight">
                                {c.desc}
                            </p>
                        </div>
                    ))}
                </section>

                {/* 3. COMMENT ÇA MARCHE EN 3 ÉTAPES CLAIRES */}
                <section className="rounded-3xl border border-line bg-surface p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="text-center max-w-xl mx-auto mb-8 sm:mb-10">
                        <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                            Simplicité & Rapidité
                        </span>
                        <h2 className="mt-2 font-display text-2xl sm:text-3xl font-bold text-ink">
                            Comment fonctionne votre affiliation ?
                        </h2>
                    </div>

                    <div className="grid gap-6 md:grid-cols-3">
                        {etapes.map((e) => (
                            <div key={e.num} className="relative rounded-2xl border border-line/80 bg-ground/50 p-5 sm:p-6 transition-all hover:bg-ground hover:shadow-xs">
                                <div className="flex items-center justify-between">
                                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-white shadow-2xs">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            {e.icon}
                                        </svg>
                                    </span>
                                    <span className="font-display text-xs font-black tracking-widest text-ink-3">
                                        {e.num}
                                    </span>
                                </div>
                                <h3 className="mt-4 font-display text-base font-bold text-ink">
                                    {e.titre}
                                </h3>
                                <p className="mt-1.5 text-xs leading-relaxed text-ink-2">
                                    {e.texte}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 4. SECTION RÉSEAU AVEC DEUXIÈME GRANDE PHOTO */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-2">
                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm order-2 lg:order-1">
                            <img
                                src="/images/affiliation-network.jpg"
                                alt="Communauté et réseau d'affiliation MALKUTHAR"
                                className="h-52 sm:h-64 md:h-72 lg:h-80 w-full object-cover object-center"
                            />
                        </div>

                        <div className="flex flex-col justify-center order-1 lg:order-2">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                Puissance du multi-niveaux
                            </span>
                            <h2 className="mt-3 font-display text-xl sm:text-2xl lg:text-3xl font-extrabold text-ink leading-tight">
                                Gagnez sur vos ventes directes et sur toute votre équipe
                            </h2>
                            <p className="mt-2 text-xs sm:text-sm leading-relaxed text-ink-2">
                                Chaque vente génère 3 commissions indépendantes calculées sur le même montant éligible, sans déduction entre partenaires :
                            </p>

                            <div className="mt-4 space-y-2.5">
                                <div className="flex items-center gap-3 rounded-xl border border-line bg-surface p-3 shadow-2xs">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-xs font-bold text-accent">
                                        N1
                                    </span>
                                    <div className="text-xs">
                                        <strong className="text-ink font-semibold">Niveau 1 ({fmt(rateL1)} %) :</strong> Vous recommandez directement un produit.
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 rounded-xl border border-line bg-surface p-3 shadow-2xs">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-xs font-bold text-accent">
                                        N2
                                    </span>
                                    <div className="text-xs">
                                        <strong className="text-ink font-semibold">Niveau 2 ({fmt(rateL2)} %) :</strong> Votre filleul parraine et réalise une vente.
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 rounded-xl border border-line bg-surface p-3 shadow-2xs">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-xs font-bold text-accent">
                                        N3
                                    </span>
                                    <div className="text-xs">
                                        <strong className="text-ink font-semibold">Niveau 3 ({fmt(rateL3)} %) :</strong> Le filleul de votre filleul génère une commande.
                                    </div>
                                </div>
                            </div>

                            <p className="mt-3.5 text-[11px] text-ink-3">
                                💡 Exemple : sur un produit payé <strong>10 000 FCFA</strong>, l'affilié direct perçoit {fmt(rateL1 * 100)} FCFA, son parrain {fmt(rateL2 * 100)} FCFA et le niveau supérieur {fmt(rateL3 * 100)} FCFA.
                            </p>
                        </div>
                    </div>
                </section>

                {/* 5. APERÇU RÉEL DU CATALOGUE AFFILIABLE (SHOWCASE) */}
                <section id="catalogue" className="space-y-4">
                    <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                                Exemples concrets
                            </span>
                            <h2 className="mt-1 font-display text-xl sm:text-2xl font-bold text-ink">
                                Produits éligibles & gains réels
                            </h2>
                        </div>
                        <p className="text-xs text-ink-3">
                            Gains estimés sur la base du Niveau 1 ({fmt(rateL1)} %)
                        </p>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-line bg-surface p-4 sm:p-6 shadow-2xs">
                        {showcase.length > 0 ? (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {showcase.map((item) => (
                                    <div key={item.slug} className="flex flex-col rounded-xl border border-line bg-ground/40 p-3 transition-all hover:bg-surface hover:shadow-xs">
                                        <div className="aspect-square overflow-hidden rounded-lg bg-ground">
                                            {item.image ? (
                                                <img src={item.image} alt={item.name} loading="lazy" className="h-full w-full object-cover" />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center text-ink-3">
                                                    <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.1">
                                                        <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 15l5-5 4 4 3-3 6 6" /><circle cx="9" cy="9" r="1.5" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                        <p className="mt-2.5 line-clamp-2 text-xs font-bold leading-snug text-ink">{item.name}</p>
                                        <div className="mt-auto pt-2 space-y-1 text-xs">
                                            <div className="flex items-baseline justify-between text-ink-3 text-[11px]">
                                                <span>Prix</span>
                                                <Price price={item.price} currency={item.currency} size="sm" />
                                            </div>
                                            <div className="flex items-baseline justify-between font-bold text-accent text-xs">
                                                <span>Votre gain direct</span>
                                                <span>{money(item.gain)} {item.currency}</span>
                                            </div>
                                        </div>
                                        <Button variant="secondary" className="mt-3 h-8 w-full justify-center px-3 text-[11px] font-semibold" href={route('catalog.show', item.slug)}>
                                            Voir le produit
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                icon="box"
                                title="Le catalogue affiliable se remplit"
                                text="Les créations et formations digitales marquées « affiliables » apparaîtront ici dès leur publication."
                            />
                        )}
                    </div>
                </section>

                {/* 6. TRANSPARENCE, SÉCURITÉ & RETRAITS */}
                <section className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded-2xl border border-line bg-surface p-5 sm:p-6 shadow-2xs">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-accent">Cycle de retrait</span>
                        <h3 className="mt-1 font-display text-base font-bold text-ink">Retraits simples et sécurisés</h3>
                        <ul className="mt-3 space-y-2 text-xs text-ink-2">
                            <li className="flex items-start gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-accent mt-1.5 shrink-0" />
                                <span>Commission enregistrée dès la commande en ligne.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-accent mt-1.5 shrink-0" />
                                <span>Disponible au retrait après {validationDelayDays} jours de délai de garantie.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-accent mt-1.5 shrink-0" />
                                <span>Retirable dès {money(minWithdrawal)} par Mobile Money ou virement.</span>
                            </li>
                        </ul>
                    </div>

                    <div className="rounded-2xl border border-line bg-surface p-5 sm:p-6 shadow-2xs">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-accent">Éthique & Règles</span>
                        <h3 className="mt-1 font-display text-base font-bold text-ink">Un partenariat transparent</h3>
                        <p className="mt-2 text-xs leading-relaxed text-ink-2">
                            L'auto-parrainage et les comptes fictifs sont proscrits. Les conditions complètes sont consultables dans nos{' '}
                            <Link
                                href={route('affiliate.program-terms')}
                                className="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent"
                            >
                                Conditions du programme d'affiliation
                            </Link>.
                        </p>
                    </div>
                </section>

                {/* 7. BANDEAU D'APPEL À L'ACTION FINAL */}
                <section className="rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-ink text-white p-6 sm:p-10 text-center shadow-lg">
                    <h2 className="font-display text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Prêt à faire grandir vos revenus avec MALKUTHAR ?
                    </h2>
                    <p className="mx-auto mt-2 max-w-md text-xs sm:text-sm text-white/80">
                        Activation gratuite et instantanée depuis votre compte en 1 clic.
                    </p>
                    <div className="mt-5 flex flex-wrap justify-center gap-3">
                        <Button variant="primary" href={joinHref} native className="h-10 px-6 font-semibold bg-accent text-white hover:bg-accent-2">
                            {auth?.user ? 'Activer mon espace affilié' : 'Créer un compte partenaire'}
                        </Button>
                        <Button variant="secondary" href={route('catalog.index', { type: 'digital' })} className="h-10 px-5 border-white/20 text-white hover:bg-white/10">
                            Explorer les formations
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}

Affiliation.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
