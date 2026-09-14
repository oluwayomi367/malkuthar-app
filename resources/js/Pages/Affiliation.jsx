import { Head, usePage } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import SectionHeading from '../Components/ui/SectionHeading';
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
    const joinHref = auth?.user ? route('affiliate.join') : route('register');

    const etapes = [
        ['Activez votre espace', <>Depuis votre compte MALKUTHAR, activez l'espace affilié en un clic. Aucun second compte : votre compte client devient aussi votre compte affilié. Vous recevez un identifiant permanent et un lien de parrainage personnel.</>],
        ['Partagez votre lien', <>Diffusez votre lien <code>{`${window.location.origin}/${referralPath}/<code>`}</code> où vous voulez : réseaux, messagerie, site. Toute personne qui arrive par ce lien vous est rattachée pendant {referralCookieDays} jours, puis de façon définitive dès qu'elle crée son compte.</>],
        ["La vente est attribuée", "Quand une personne rattachée achète un produit digital éligible, la commande est reliée à vous. L'attribution du parrain est immuable : elle ne peut plus changer ensuite."],
        ['La commission est créditée', 'Dès le paiement confirmé, les commissions des trois niveaux sont inscrites dans votre registre au statut « en attente ».'],
    ];

    const suivi = [
        ['Mes ventes', 'Chaque commande référée : produit, date, montant éligible, niveau, commission et statut.'],
        ['Ma lignée', 'Vos filleuls sur trois niveaux : nombre, actifs, ventes réalisées et chiffre d\'affaires généré.'],
        ['Mes gains', 'Solde disponible, total des commissions et total retiré — reconstitués depuis le registre, pas une simple variable.'],
    ];

    const regles = [
        "L'auto-parrainage est interdit : vous ne pouvez pas être votre propre référent sur une commande.",
        'Les comptes multiples ou fictifs et la manipulation des liens entraînent la suspension.',
        'Le parrain est définitif dès le rattachement — ni vous ni le filleul ne pouvez le changer.',
        "En cas de fraude ou de violation des conditions, l'espace affilié peut être suspendu. Aucune donnée financière n'est supprimée.",
    ];

    const cycle = [
        ['En attente', "La commission est créée dès le paiement. Elle n'est pas encore retirable."],
        ['Disponible', `Après ${validationDelayDays} jours sans remboursement, litige ni fraude, elle devient retirable.`],
        ['Demande de retrait', `Vous déclenchez vous-même le retrait depuis votre espace, dès que le solde disponible atteint ${money(minWithdrawal)}.`],
        ['Contrôles de sécurité', `Vérification par code si besoin. Toute modification du moyen de retrait ouvre un délai de ${cooldownHours} h avant le prochain retrait. Des frais peuvent s'appliquer, affichés avant confirmation.`],
        ['Versement', 'Le paiement est envoyé sur le moyen de retrait choisi et tracé dans votre registre.'],
    ];

    const niveaux = [
        ['Niveau 1', 'Vos ventes directes', rateL1, 'La personne que vous avez parrainée achète.'],
        ['Niveau 2', 'Les ventes de vos filleuls', rateL2, "Un filleul de votre filleul achète."],
        ['Niveau 3', 'Le niveau suivant', rateL3, 'Encore un cran en dessous — puis plus rien.'],
    ];

    const chiffres = [
        ['Niveaux rémunérés', paidLevels, `Aucune commission au-delà du ${paidLevels}ᵉ niveau.`],
        ['Taux par niveau', `${fmt(rateL1)} · ${fmt(rateL2)} · ${fmt(rateL3)} %`, 'Calculés indépendamment sur le même montant éligible.'],
        ['Validation', `${validationDelayDays} jours`, "Délai avant qu'une commission devienne retirable."],
        ['Retrait minimum', money(minWithdrawal), 'Dans la devise de votre compte.'],
    ];

    return (
        <>
            <Head title="Programme d'affiliation">
                <meta name="description" content={`Comment fonctionne le programme d'affiliation MALKUTHAR : trois niveaux de commission (${rateL1} / ${rateL2} / ${rateL3} %) sur les produits digitaux éligibles, du lien de parrainage au versement.`} />
            </Head>

            <section className="mx-auto max-w-[1180px] px-4 pb-12 pt-14 text-center sm:px-6 sm:pt-20">
                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Programme d'affiliation</p>
                <h1 className="mx-auto mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
                    Gagnez en recommandant des produits que vous aimez.
                </h1>
                <p className="mx-auto mt-4 max-w-xl text-ink-2">
                    Rejoignez le réseau d'affiliation de MALKUTHAR. Partagez votre lien, et touchez une commission sur trois
                    niveaux à chaque vente d'un produit digital éligible — la vôtre, et celles de votre lignée.
                </p>
                <div className="mt-6 flex flex-wrap justify-center gap-3">
                    <Button variant="primary" href={joinHref} native>{auth?.user ? 'Activer mon espace affilié' : 'Rejoindre le réseau'}</Button>
                    <Button variant="secondary" href="#catalogue" native>Explorer le catalogue →</Button>
                </div>
            </section>

            <section id="catalogue" className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <div className="overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_8px_30px_rgba(46,44,40,0.08)]">
                    <div className="flex items-center gap-2 border-b border-line bg-ground-alt px-4 py-3">
                        <span className="h-2.5 w-2.5 rounded-full bg-line-strong" />
                        <span className="h-2.5 w-2.5 rounded-full bg-line-strong" />
                        <span className="h-2.5 w-2.5 rounded-full bg-line-strong" />
                        <span className="ml-3 text-[12px] font-medium text-ink-3">Espace affilié · Catalogue</span>
                    </div>
                    <div className="flex flex-col sm:flex-row">
                        <nav className="hidden shrink-0 border-r border-line bg-ground-alt/60 px-3 py-5 sm:block sm:w-[168px]">
                            <ul className="space-y-0.5 text-[13px] text-ink-2">
                                {['Accueil', 'Catalogue', 'Mes liens', 'Ventes', 'Revenus', 'Vitrine', 'Paramètres'].map((label, i) => (
                                    <li key={label} className={['flex items-center gap-2 rounded-lg px-2.5 py-2', i === 1 ? 'bg-accent-soft/60 font-semibold text-ink' : ''].join(' ')}>
                                        <span className={['h-1.5 w-1.5 shrink-0 rounded-full', i === 1 ? 'bg-accent' : 'bg-line-strong'].join(' ')} />
                                        {label}
                                    </li>
                                ))}
                            </ul>
                        </nav>

                        <div className="flex-1 p-4 sm:p-6">
                            {showcase.length > 0 ? (
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    {showcase.map((item) => (
                                        <div key={item.slug} className="rounded-xl border border-line bg-surface p-3">
                                            <div className="aspect-square overflow-hidden rounded-lg bg-ground-alt">
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
                                            <p className="mt-2.5 line-clamp-2 text-[13px] font-medium leading-snug text-ink">{item.name}</p>
                                            <div className="mt-2 space-y-1 text-[12px]">
                                                <p className="flex items-baseline justify-between text-ink-3">
                                                    <span>Prix</span>
                                                    <Price price={item.price} currency={item.currency} size="sm" />
                                                </p>
                                                <p className="flex items-baseline justify-between font-semibold text-accent">
                                                    <span>Vos gains ({fmt(rateL1)} %)</span>
                                                    <span>{money(item.gain)} {item.currency}</span>
                                                </p>
                                            </div>
                                            <Button variant="secondary" className="mt-2.5 h-9 w-full justify-center px-3 text-xs" href={route('catalog.show', item.slug)}>
                                                Voir le produit
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <EmptyState icon="box" title="Le catalogue affiliable se remplit"
                                            text="Les produits digitaux marqués « affiliables » apparaîtront ici dès leur publication." />
                            )}
                        </div>
                    </div>
                </div>
                <p className="mt-3 text-center text-[13px] text-ink-2">
                    Aperçu réel du catalogue affiliable — les gains affichés sont calculés au taux de niveau 1 en vigueur.
                </p>
            </section>

            <section className="border-y border-line bg-ground-alt">
                <div className="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-2 lg:grid-cols-4">
                    {chiffres.map(([label, value, foot]) => (
                        <div key={label} className="bg-ground-alt px-4 py-8 sm:px-6">
                            <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">{label}</p>
                            <p className="mt-2 font-display text-2xl font-semibold tracking-[-0.01em] text-ink">{value}</p>
                            <p className="mt-2 text-[13px] leading-snug text-ink-2">{foot}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="Le parcours" title="Comment ça marche" />
                <ol className="grid gap-6 sm:grid-cols-2">
                    {etapes.map(([titre, texte], i) => (
                        <li key={titre} className="flex gap-4">
                            <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-sm font-semibold text-accent">
                                {i + 1}
                            </span>
                            <div>
                                <p className="font-display text-[15px] font-semibold text-ink">{titre}</p>
                                <p className="mt-1 text-sm leading-relaxed text-ink-2">{texte}</p>
                            </div>
                        </li>
                    ))}
                </ol>
            </section>

            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                    <SectionHeading eyebrow="La rémunération" title="Trois niveaux, calculés séparément" />
                    <p className="max-w-2xl text-sm text-ink-2">
                        Pour chaque vente éligible, trois commissions sont générées en parallèle, chacune calculée{' '}
                        <strong className="text-ink">sur le même montant éligible</strong> — jamais en cascade les unes sur les
                        autres. Au total, {fmt(rateTotal)} % du montant éligible est distribué.
                    </p>
                    <div className="mt-8 grid gap-3 sm:grid-cols-3">
                        {niveaux.map(([niv, sous, taux, ex]) => (
                            <div key={niv} className="rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(46,44,40,0.04)]">
                                <div className="flex items-baseline justify-between">
                                    <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">{niv}</p>
                                    <p className="font-display text-2xl font-bold text-accent">{fmt(taux)} %</p>
                                </div>
                                <p className="mt-2 text-[15px] font-semibold text-ink">{sous}</p>
                                <p className="mt-1 text-[13px] leading-snug text-ink-2">{ex}</p>
                            </div>
                        ))}
                    </div>
                    <div className="mt-6 rounded-xl border border-line bg-surface px-4 py-3 text-[13px] text-ink-2">
                        <span className="font-semibold text-ink">Exemple —</span> sur un produit éligible payé <strong>10 000</strong>{' '}
                        (net de coupon), l'affilié direct reçoit {fmt(rateL1 * 100)}, son parrain {fmt(rateL2 * 100)} et le niveau
                        au-dessus {fmt(rateL3 * 100)}.
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="Le périmètre" title="Ce qui génère une commission" />
                <div className="grid gap-6 sm:grid-cols-2">
                    <div className="rounded-2xl border border-brand-green/25 bg-brand-green/[0.06] p-5">
                        <p className="font-display text-[15px] font-semibold text-ink">Produits digitaux éligibles</p>
                        <p className="mt-1 text-sm leading-relaxed text-ink-2">
                            Seuls les produits digitaux explicitement déclarés « affiliables » rémunèrent. Ils portent une mention
                            visible sur leur fiche.
                        </p>
                    </div>
                    <div className="rounded-2xl border border-line bg-surface p-5">
                        <p className="font-display text-[15px] font-semibold text-ink">Physiques &amp; services</p>
                        <p className="mt-1 text-sm leading-relaxed text-ink-2">
                            Les produits physiques et les prestations de service ne génèrent <strong className="text-ink">aucune</strong>{' '}
                            commission. Dans une commande mixte, seule la part digitale éligible est prise en compte.
                        </p>
                    </div>
                </div>
                <p className="mt-5 text-sm text-ink-2">
                    La commission est calculée sur le montant <strong className="text-ink">réellement payé</strong> pour le produit
                    éligible, <strong className="text-ink">après application d'un éventuel coupon</strong>. Les taux et les produits
                    concernés peuvent faire l'objet de campagnes promotionnelles.
                </p>
            </section>

            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                    <SectionHeading eyebrow="Le cycle de vie" title="Du clic au versement" />
                    <ol className="space-y-4">
                        {cycle.map(([etat, desc], i) => (
                            <li key={etat} className="flex gap-4 rounded-xl border border-line bg-surface px-4 py-3.5">
                                <span className="mt-0.5 font-display text-sm font-semibold text-ink-3">{String(i + 1).padStart(2, '0')}</span>
                                <div>
                                    <p className="text-[14px] font-semibold text-ink">{etat}</p>
                                    <p className="mt-0.5 text-[13px] leading-snug text-ink-2">{desc}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                    <p className="mt-5 text-sm text-ink-2">
                        <strong className="text-ink">En cas de remboursement</strong> d'une vente, la commission correspondante est
                        annulée si elle était encore en attente, ou reprise si elle était déjà disponible ou payée.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="La transparence" title="Tout est suivi dans votre espace" />
                <div className="grid gap-8 sm:grid-cols-3">
                    {suivi.map(([titre, texte]) => (
                        <div key={titre}>
                            <p className="text-[15px] font-semibold text-ink">{titre}</p>
                            <p className="mt-1 text-sm text-ink-2">{texte}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                    <SectionHeading eyebrow="Les règles" title="Ce qui est interdit" />
                    <ul className="space-y-3">
                        {regles.map((regle) => (
                            <li key={regle} className="flex gap-3 text-sm text-ink-2">
                                <svg className="mt-0.5 h-4 w-4 shrink-0 text-rust" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6">
                                    <path d="M6 6l8 8M14 6l-8 8" strokeLinecap="round" />
                                </svg>
                                <span>{regle}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-6 text-sm text-ink-2">
                        Le détail contractuel complet figure dans les{' '}
                        <a href={route('affiliate.program-terms')} className="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">
                            Conditions du programme d'affiliation
                        </a>. Cette page-ci en explique le fonctionnement ; les Conditions font foi.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-14 text-center sm:px-6">
                <h2 className="font-display text-2xl font-bold tracking-[-0.01em] text-ink">Prêt à recommander MALKUTHAR ?</h2>
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-2">L'activation est gratuite et immédiate. Vous gardez le même compte.</p>
                <div className="mt-5 flex flex-wrap justify-center gap-3">
                    <Button variant="primary" href={joinHref} native>{auth?.user ? 'Activer mon espace affilié' : 'Créer un compte'}</Button>
                    <Button variant="secondary" href={route('catalog.index', { type: 'digital' })}>Voir les produits digitaux</Button>
                </div>
            </section>
        </>
    );
}

Affiliation.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
