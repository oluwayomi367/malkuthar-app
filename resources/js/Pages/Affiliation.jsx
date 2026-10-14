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
    const joinLabel = auth?.user ? 'Activer mon espace affilié' : 'Rejoindre le programme';

    const niveaux = [
        { rate: rateL1, title: 'Niveau 1', text: 'Une personne achète via votre lien.' },
        { rate: rateL2, title: 'Niveau 2', text: 'Un de vos filleuls réalise une vente.' },
        { rate: rateL3, title: 'Niveau 3', text: 'Le filleul d’un filleul réalise une vente.' },
    ];

    const etapes = [
        {
            titre: 'Activez votre lien',
            texte: `Depuis votre compte, en un clic. Votre lien mémorise les visiteurs pendant ${referralCookieDays} jours.`,
        },
        {
            titre: 'Partagez-le',
            texte: 'Sur vos réseaux, WhatsApp, une chaîne ou un blog. Tout achat passé via ce lien vous est rattaché.',
        },
        {
            titre: 'Retirez vos gains',
            texte: `Les commissions deviennent retirables après ${validationDelayDays} jours, par Mobile Money ou virement.`,
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

            {/* Ouverture */}
            <section className="border-b border-line">
                <div className="mx-auto grid max-w-[1280px] items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:py-20">
                    <div>
                        <p className="text-sm font-medium text-accent-2">Programme d'affiliation</p>
                        <h1 className="mt-3 text-[clamp(2.25rem,5vw,3.5rem)] font-semibold leading-[1.06] text-ink">
                            Recommandez, et gagnez sur trois niveaux de ventes
                        </h1>
                        <p className="mt-6 max-w-xl text-base leading-relaxed text-ink-2 sm:text-lg">
                            Partagez les formations et produits digitaux de MALKUTHAR. Jusqu'à{' '}
                            <strong className="font-semibold text-ink">{fmt(rateTotal)} %</strong> de commission par vente,
                            répartis entre vous et votre réseau.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button variant="primary" href={joinHref} native>{joinLabel}</Button>
                            <Button variant="secondary" href="#catalogue" native>Voir les produits éligibles</Button>
                        </div>
                    </div>
                    <div className="overflow-hidden rounded-2xl bg-ground-alt">
                        <img
                            src="/images/affiliation-hero.jpg"
                            alt="Affiliée partageant un produit MALKUTHAR"
                            className="aspect-[4/3] h-full w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-[1280px] space-y-20 px-4 py-16 sm:px-6 sm:py-20 lg:space-y-28">
                {/* Taux */}
                <section aria-labelledby="taux-titre">
                    <h2 id="taux-titre" className="text-3xl text-ink sm:text-4xl">Trois niveaux de commission</h2>
                    <p className="mt-3 max-w-2xl text-[15px] leading-relaxed text-ink-2">
                        Chaque vente génère trois commissions indépendantes, calculées sur le même montant éligible,
                        sans déduction entre partenaires.
                    </p>

                    <ul className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {niveaux.map((n) => (
                            <li key={n.title} className="rounded-2xl border border-line bg-surface p-6">
                                <p className="text-sm font-medium text-ink-2">{n.title}</p>
                                <p className="mt-2 font-display text-5xl font-semibold leading-none text-accent-2">{fmt(n.rate)} %</p>
                                <p className="mt-4 text-sm leading-relaxed text-ink-2">{n.text}</p>
                            </li>
                        ))}
                        <li className="rounded-2xl bg-ground-alt p-6">
                            <p className="text-sm font-medium text-ink-2">Retrait minimum</p>
                            <p className="mt-2 font-display text-[40px] font-semibold leading-none text-ink">{money(minWithdrawal)}</p>
                            <p className="mt-4 text-sm leading-relaxed text-ink-2">Après {validationDelayDays} jours de délai de garantie.</p>
                        </li>
                    </ul>

                    <p className="mt-6 text-sm leading-relaxed text-ink-2">
                        Exemple : pour un produit payé 10 000 FCFA, l'affilié direct perçoit {fmt(rateL1 * 100)} FCFA,
                        son parrain {fmt(rateL2 * 100)} FCFA et le niveau suivant {fmt(rateL3 * 100)} FCFA.
                    </p>
                </section>

                {/* Étapes */}
                <section aria-labelledby="etapes-titre" className="grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:gap-16">
                    <div>
                        <h2 id="etapes-titre" className="text-3xl text-ink sm:text-4xl">Comment ça fonctionne</h2>
                        <div className="mt-8 hidden overflow-hidden rounded-2xl bg-ground-alt lg:block">
                            <img src="/images/affiliation-network.jpg" alt="Réseau d'affiliés MALKUTHAR" loading="lazy"
                                 className="aspect-[4/3] w-full object-cover" />
                        </div>
                    </div>
                    <ol className="space-y-0">
                        {etapes.map((e, i) => (
                            <li key={e.titre} className="flex gap-5 border-t border-line-strong py-7 first:border-t-0 first:pt-0">
                                <span className="font-display text-3xl font-semibold leading-none text-accent-2" aria-hidden="true">{i + 1}</span>
                                <div>
                                    <h3 className="text-lg text-ink">{e.titre}</h3>
                                    <p className="mt-2 text-[15px] leading-relaxed text-ink-2">{e.texte}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </section>

                {/* Produits éligibles */}
                <section id="catalogue" aria-labelledby="catalogue-titre" className="scroll-mt-24">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <h2 id="catalogue-titre" className="text-3xl text-ink sm:text-4xl">Produits éligibles</h2>
                        <p className="text-sm text-ink-2">Gain calculé au niveau 1 ({fmt(rateL1)} %)</p>
                    </div>

                    <div className="mt-8">
                        {showcase.length > 0 ? (
                            <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                {showcase.map((item) => (
                                    <li key={item.slug} className="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface">
                                        <div className="aspect-square bg-ground-alt">
                                            {item.image ? (
                                                <img src={item.image} alt={item.name} loading="lazy" className="h-full w-full object-cover" />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center text-ink-3">
                                                    <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
                                                        <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 15l5-5 4 4 3-3 6 6" /><circle cx="9" cy="9" r="1.5" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex flex-1 flex-col p-5">
                                            <h3 className="line-clamp-2 text-base text-ink">{item.name}</h3>
                                            <dl className="mt-4 space-y-1.5 text-sm">
                                                <div className="flex items-baseline justify-between gap-3 text-ink-2">
                                                    <dt>Prix</dt>
                                                    <dd><Price price={item.price} currency={item.currency} size="sm" /></dd>
                                                </div>
                                                <div className="flex items-baseline justify-between gap-3">
                                                    <dt className="text-ink-2">Votre gain</dt>
                                                    <dd className="font-semibold text-accent-2">{money(item.gain)} {item.currency}</dd>
                                                </div>
                                            </dl>
                                            <div className="mt-auto pt-5">
                                                <Button variant="secondary" full href={route('catalog.show', item.slug)}>
                                                    Voir le produit
                                                </Button>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon="box"
                                title="Aucun produit éligible pour le moment"
                                text="Les produits digitaux ouverts à l'affiliation apparaîtront ici dès leur publication."
                            />
                        )}
                    </div>
                </section>

                {/* Retraits et règles */}
                <section className="grid gap-5 md:grid-cols-2" aria-label="Retraits et règles">
                    <div className="rounded-2xl border border-line bg-surface p-6 sm:p-8">
                        <h3 className="text-lg text-ink">Retraits</h3>
                        <ul className="mt-4 space-y-3 text-[15px] leading-relaxed text-ink-2">
                            <li className="border-l-2 border-line-strong pl-4">Commission enregistrée dès la commande payée.</li>
                            <li className="border-l-2 border-line-strong pl-4">Disponible au retrait après {validationDelayDays} jours de délai de garantie.</li>
                            <li className="border-l-2 border-line-strong pl-4">Retirable dès {money(minWithdrawal)}, par Mobile Money ou virement.</li>
                        </ul>
                    </div>
                    <div className="rounded-2xl border border-line bg-surface p-6 sm:p-8">
                        <h3 className="text-lg text-ink">Règles</h3>
                        <p className="mt-4 text-[15px] leading-relaxed text-ink-2">
                            L'auto-parrainage et les comptes fictifs sont interdits. Les conditions complètes figurent dans les{' '}
                            <Link
                                href={route('affiliate.program-terms')}
                                className="font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-accent"
                            >
                                Conditions du programme d'affiliation
                            </Link>.
                        </p>
                    </div>
                </section>

                {/* Appel final */}
                <section className="flex flex-col gap-8 rounded-2xl bg-ink p-7 text-white sm:p-10 md:flex-row md:items-center md:justify-between">
                    <div className="max-w-xl">
                        <h2 className="text-3xl text-white">Prêt à partager votre lien ?</h2>
                        <p className="mt-3 text-[15px] leading-relaxed text-night-ink-2">
                            L'activation est gratuite et se fait depuis votre compte.
                        </p>
                    </div>
                    <div className="flex shrink-0 flex-col gap-3 sm:flex-row">
                        <a href={joinHref}
                           className="inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2">
                            {auth?.user ? 'Activer mon espace affilié' : 'Créer un compte partenaire'}
                        </a>
                        <Link href={route('catalog.index', { type: 'digital' })}
                              className="inline-flex min-h-11 items-center justify-center rounded-full border border-night-line px-6 text-sm font-semibold text-night-ink transition-colors hover:border-night-ink-2">
                            Explorer les formations
                        </Link>
                    </div>
                </section>
            </div>
        </>
    );
}

Affiliation.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
