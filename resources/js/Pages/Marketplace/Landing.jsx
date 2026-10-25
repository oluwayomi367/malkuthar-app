import { useState } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import { useImages } from '../../lib/images';
import Button from '../../Components/ui/Button';

const POLES = [
    {
        key: 'fashion_beauty',
        poleSlug: 'mode-beaute-cosmetiques',
        titre: 'Mode, beauté et cosmétiques',
        subtitle: 'Cosmétiques naturels, créateurs de mode, maroquinerie, cordonnerie.',
        photoKey: 'marketplace.landing_mode',
        points: [
            'Le client paie en ligne avant l\'expédition (Mobile Money ou carte).',
            'Gestion des tailles, pointures, contenances et du stock par variante.',
            'Vos articles peuvent être recommandés par les affiliés MALKUTHAR.',
        ],
    },
    {
        key: 'real_estate_hospitality',
        poleSlug: 'residences-guest-houses-immobilier',
        titre: 'Résidences, guest houses et immobilier',
        subtitle: 'Guest houses, résidences meublées de court séjour, agences agréées.',
        photoKey: 'marketplace.landing_residence',
        points: [
            'Paiement ou acompte encaissé avant l\'arrivée du voyageur.',
            'Politique d\'annulation affichée sur chaque annonce.',
            'Commission prélevée uniquement sur les réservations payées.',
        ],
    },
    {
        key: 'services_wellness',
        poleSlug: 'services-praticiens-specialises',
        titre: 'Services et praticiens',
        subtitle: 'Thérapeutes, accompagnants bien-être, coachs, consultants.',
        photoKey: 'marketplace.landing_service',
        points: [
            'Le client choisit un créneau dans votre agenda et paie à la réservation.',
            'Consultation en visio (lien envoyé) ou en cabinet (adresse transmise).',
            'Vos prestations sont présentées à part des articles en vente.',
        ],
    },
    {
        key: 'digital_academy',
        poleSlug: 'savoirs-formations-en-ligne',
        titre: 'Savoirs et formations en ligne',
        subtitle: 'Formateurs, auteurs, conférenciers, animateurs d\'ateliers.',
        photoKey: 'marketplace.landing_digital',
        points: [
            'Accès au contenu ouvert automatiquement après paiement.',
            'Programme d\'affiliation sur trois niveaux pour vos formations.',
            'Versements de vos gains par Mobile Money ou virement.',
        ],
    },
];

const ETAPES = [
    ['Candidature', 'Choisissez votre univers et présentez votre activité dans le formulaire vendeur.'],
    ['Vérification du compte', 'Notre équipe examine chaque candidature sous 24 à 48 heures ouvrées.'],
    ['Publication des offres', 'Chaque fiche est relue avant d\'être visible sur la place de marché.'],
    ['Ventes et versements', 'Les montants nets sont crédités sur votre solde vendeur, puis versés à votre demande.'],
];

const FAQ = [
    [
        'Pourquoi les comptes et les offres sont-ils validés manuellement ?',
        'La vérification permet de s\'assurer de l\'identité des vendeurs et de la conformité des offres. Elle protège les acheteurs comme les vendeurs contre la contrefaçon et les annonces frauduleuses.',
    ],
    [
        'Puis-je inscrire ma guest house ou mon agence immobilière ?',
        'Oui. Le pôle Résidences, guest houses et immobilier accueille les hébergements meublés de court séjour et les agences agréées, avec paiement ou acompte en ligne.',
    ],
    [
        'Comment les univers sont-ils séparés ?',
        'Les quatre pôles ont chacun leur catalogue et leurs critères de recherche (nuitées, créneaux, tailles…). Une recherche dans un pôle n\'affiche pas les offres des autres.',
    ],
    [
        'Combien de temps prend la validation ?',
        'Les dossiers sont traités sous 24 à 48 heures ouvrées. Vous recevez un email dès qu\'une décision est prise et votre statut est mis à jour dans votre espace.',
    ],
    [
        'Comment la commission est-elle calculée ?',
        'Une commission est prélevée uniquement sur les ventes effectives. Son taux peut varier selon le type d\'offre ; il est indiqué dans les conditions vendeurs.',
    ],
    [
        'Y a-t-il des frais fixes ou un abonnement ?',
        'Non. Vous ne payez rien tant que vous ne vendez pas. Les fiches d\'offre, l\'agenda de réservation, l\'encaissement et le support sont inclus.',
    ],
    [
        'Quand reçois-je mes versements ?',
        'Une fois la commande expédiée, le séjour terminé ou la consultation effectuée, le montant net est crédité sur votre solde. Vous pouvez demander un versement par Mobile Money (Wave, MTN, Moov, Orange) ou par virement.',
    ],
];

const fmt = (n) => {
    const s = Number(n).toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return s.replace('.', ',');
};

const Check = () => (
    <svg className="mt-0.5 h-5 w-5 shrink-0 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M5 12.5l4.5 4.5L19 7.5" />
    </svg>
);

export default function Landing({ enabled, stats, starterRate, growthRate, growthThreshold, userVendor }) {
    const img = useImages();
    const { auth } = usePage().props;
    const [openFaq, setOpenFaq] = useState(null);

    // Simulateur interactif de revenus
    const [unitPrice, setUnitPrice] = useState(15000);
    const [monthlySales, setMonthlySales] = useState(12);

    const rate = (starterRate || 15) / 100;
    const totalGross = Math.max(0, unitPrice) * Math.max(1, monthlySales);
    const totalFee = Math.round(totalGross * rate);
    const totalNet = totalGross - totalFee;

    const isActive = auth?.user && userVendor?.status === 'active';
    const primaryHref = auth?.user ? (userVendor?.status === 'active' ? route('vendor.dashboard') : route('vendor.application')) : route('register.vendor');

    return (
        <>
            <Head title="Vendre sur MALKUTHAR — Place de marché">
                <meta name="description" content="Ouvrez votre boutique sur MALKUTHAR : produits physiques, digitaux, consultations et formations. Inscription vendeur, validation manuelle de chaque offre." />
            </Head>

            {/* Bandeau d'état pour l'utilisateur connecté */}
            {auth?.user && (
                <section className="border-b border-line bg-accent-soft">
                    <div className="mx-auto flex max-w-[1280px] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <p className="text-sm text-ink">
                            {userVendor?.status === 'active' ? (
                                <>Vous êtes connecté en tant que vendeur : <strong className="font-semibold">{userVendor.displayName}</strong>. Boutique active.</>
                            ) : userVendor?.status === 'pending' ? (
                                <>Votre demande pour la boutique <strong className="font-semibold">{userVendor.displayName}</strong> est en cours de validation.</>
                            ) : (
                                <>Connecté en tant que <strong className="font-semibold">{auth.user.name}</strong>. Vous pouvez candidater avec ce compte.</>
                            )}
                        </p>
                        {userVendor?.status === 'active' ? (
                            <Button variant="secondary" href={route('vendor.dashboard')} native>Mon espace vendeur</Button>
                        ) : userVendor?.status === 'pending' ? (
                            <Button variant="secondary" href={route('vendor.application.status')} native>Suivre ma candidature</Button>
                        ) : (
                            <Button variant="secondary" href={route('vendor.application')} native>Candidater avec ce compte</Button>
                        )}
                    </div>
                </section>
            )}

            {/* En-tête */}
            <section className="border-b border-line">
                <div className="mx-auto grid max-w-[1280px] gap-10 px-4 pb-14 pt-12 sm:px-6 sm:pt-16 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:items-end lg:gap-16 lg:pb-20">
                    <div>
                        <p className="text-sm font-semibold text-accent-2">Programme vendeur</p>
                        <h1 className="mt-3 max-w-3xl font-display text-[40px] font-semibold leading-[1.08] text-ink sm:text-[56px]">
                            Vendez vos créations, séjours et prestations sur MALKUTHAR
                        </h1>
                        <p className="mt-5 max-w-2xl text-lg leading-[1.6] text-ink-2">
                            Pour les praticiens, créateurs, formateurs et artisans. Le paiement est encaissé en ligne, chaque offre est vérifiée avant publication. Pas d'abonnement : une commission sur les ventes réalisées.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            {isActive ? (
                                <Button variant="primary" href={route('vendor.dashboard')} native>Accéder à mon espace vendeur</Button>
                            ) : auth?.user && userVendor?.status === 'pending' ? (
                                <Button variant="primary" href={route('vendor.application.status')} native>Voir le statut de ma candidature</Button>
                            ) : auth?.user ? (
                                <Button variant="primary" href={route('vendor.application')} native>Ouvrir ma boutique</Button>
                            ) : (
                                <Button variant="primary" href={route('register.vendor')} native>Créer mon compte vendeur</Button>
                            )}
                            <Button variant="secondary" href={route('marketplace.shop')}>Voir la place de marché</Button>
                        </div>
                    </div>

                    <ul className="space-y-4 border-t border-line pt-6 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0">
                        {[
                            ['Vendeurs vérifiés', 'Chaque compte est examiné sous 24 à 48 h.'],
                            ['Offres relues', 'Chaque fiche est validée avant d\'être en ligne.'],
                            ['Sans abonnement', 'Vous payez une commission uniquement quand vous vendez.'],
                        ].map(([t, d]) => (
                            <li key={t} className="flex gap-3">
                                <Check />
                                <p className="text-base text-ink-2"><span className="font-semibold text-ink">{t}.</span> {d}</p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* Chiffres (issus de la base) */}
            {stats && (
                <section className="border-b border-line bg-ground-alt">
                    <dl className="mx-auto grid max-w-[1280px] grid-cols-2 divide-x divide-line">
                        <div className="flex flex-col-reverse px-4 py-8 text-center sm:px-6">
                            <dt className="mt-2 text-sm text-ink-2">boutique{stats.vendors > 1 ? 's' : ''} active{stats.vendors > 1 ? 's' : ''}</dt>
                            <dd className="font-display text-[40px] font-semibold leading-none text-ink">{stats.vendors}</dd>
                        </div>
                        <div className="flex flex-col-reverse px-4 py-8 text-center sm:px-6">
                            <dt className="mt-2 text-sm text-ink-2">offre{stats.offers > 1 ? 's' : ''} en ligne</dt>
                            <dd className="font-display text-[40px] font-semibold leading-none text-ink">{stats.offers}</dd>
                        </div>
                    </dl>
                </section>
            )}

            {/* Les quatre pôles */}
            <section className="mx-auto max-w-[1280px] px-4 py-16 sm:px-6 lg:py-20" aria-labelledby="poles">
                <div className="max-w-2xl">
                    <h2 id="poles" className="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Quatre univers distincts</h2>
                    <p className="mt-3 text-base leading-[1.6] text-ink-2">
                        Chaque secteur a son propre catalogue et ses propres critères de recherche. Une personne qui cherche un séjour ou une consultation ne voit pas d'articles de mode.
                    </p>
                </div>

                <div className="mt-10 grid gap-6 md:grid-cols-2">
                    {POLES.map((pole) => (
                        <article key={pole.key} className="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_1px_2px_rgba(28,25,23,0.04)]">
                            <div className="aspect-[16/9] w-full overflow-hidden bg-ground-alt">
                                <img src={img(pole.photoKey)} alt="" loading="lazy" className="h-full w-full object-cover" />
                            </div>
                            <div className="flex flex-1 flex-col p-6">
                                <h3 className="text-lg font-semibold text-ink">{pole.titre}</h3>
                                <p className="mt-1 text-sm text-ink-2">{pole.subtitle}</p>

                                <ul className="mt-5 space-y-3 border-t border-line pt-5">
                                    {pole.points.map((pt) => (
                                        <li key={pt} className="flex gap-3 text-sm leading-[1.6] text-ink-2">
                                            <Check />
                                            <span>{pt}</span>
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-auto flex flex-wrap items-center gap-x-5 gap-y-2 pt-6">
                                    <Button variant="primary" href={primaryHref} native>
                                        {isActive ? 'Mon espace vendeur' : 'Candidater dans ce pôle'}
                                    </Button>
                                    <Link href={route('marketplace.shop', { pole: pole.poleSlug })}
                                          className="inline-flex min-h-11 items-center text-sm font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent-2">
                                        Voir les offres de ce pôle
                                    </Link>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </section>

            {/* Étapes */}
            <section className="border-y border-line bg-ground-alt" aria-labelledby="etapes">
                <div className="mx-auto max-w-[1280px] px-4 py-16 sm:px-6 lg:py-20">
                    <h2 id="etapes" className="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Devenir vendeur en quatre étapes</h2>
                    <ol className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {ETAPES.map(([titre, desc], i) => (
                            <li key={titre} className="border-t border-line-strong pt-5">
                                <span className="font-display text-[32px] font-semibold leading-none text-accent-2">{i + 1}</span>
                                <h3 className="mt-3 text-base font-semibold text-ink">{titre}</h3>
                                <p className="mt-2 text-sm leading-[1.6] text-ink-2">{desc}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* Simulateur */}
            <section className="mx-auto max-w-[1280px] px-4 py-16 sm:px-6 lg:py-20" aria-labelledby="simulateur">
                <div className="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-start lg:gap-16">
                    <div>
                        <h2 id="simulateur" className="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Estimer vos revenus</h2>
                        <p className="mt-3 max-w-prose text-base leading-[1.6] text-ink-2">
                            Saisissez un prix et un volume de ventes mensuel. Le calcul applique le taux de commission de base ; certains types d'offres ont un taux différent.
                        </p>

                        <div className="mt-8 space-y-6">
                            <div>
                                <label htmlFor="sim-prix" className="block text-sm font-semibold text-ink">Prix de vente unitaire (FCFA)</label>
                                <input
                                    id="sim-prix"
                                    type="number"
                                    min="500"
                                    step="500"
                                    inputMode="numeric"
                                    value={unitPrice}
                                    onChange={(e) => setUnitPrice(Number(e.target.value))}
                                    className="mt-2 h-11 w-full max-w-xs rounded-xl border border-line-strong bg-surface px-4 text-base font-semibold text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                                />
                            </div>

                            <div>
                                <label htmlFor="sim-ventes" className="block text-sm font-semibold text-ink">Ventes par mois : {monthlySales}</label>
                                <input
                                    id="sim-ventes"
                                    type="range"
                                    min="1"
                                    max="100"
                                    value={monthlySales}
                                    onChange={(e) => setMonthlySales(Number(e.target.value))}
                                    className="mt-3 h-2 w-full max-w-xs accent-accent"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-7">
                        <h3 className="text-base font-semibold text-ink">Estimation mensuelle</h3>
                        <dl className="mt-4 divide-y divide-line text-sm">
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="text-ink-2">Chiffre d'affaires</dt>
                                <dd className="font-semibold text-ink">{totalGross.toLocaleString('fr-FR')} FCFA</dd>
                            </div>
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="text-ink-2">Commission ({fmt(rate * 100)} %)</dt>
                                <dd className="text-ink-2">− {totalFee.toLocaleString('fr-FR')} FCFA</dd>
                            </div>
                            <div className="pt-4">
                                <dt className="text-sm font-semibold text-ink">Revenu net estimé</dt>
                                <dd className="mt-1 font-display text-[40px] font-semibold leading-none text-ink">
                                    {totalNet.toLocaleString('fr-FR')} <span className="font-sans text-base font-medium text-ink-3">FCFA</span>
                                </dd>
                            </div>
                        </dl>
                        <p className="mt-3 text-xs text-ink-3">
                            Montant indicatif, crédité sur votre solde vendeur et disponible au retrait par Mobile Money ou virement.
                        </p>
                        <div className="mt-6 border-t border-line pt-5">
                            <Button variant="primary" full href={primaryHref} native>
                                {isActive ? 'Accéder à mon espace' : 'Commencer à vendre'}
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            {/* FAQ */}
            <section className="border-t border-line" aria-labelledby="faq">
                <div className="mx-auto max-w-[1280px] px-4 py-16 sm:px-6 lg:py-20">
                    <h2 id="faq" className="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Questions fréquentes</h2>

                    <div className="mt-8 max-w-3xl divide-y divide-line border-y border-line">
                        {FAQ.map(([q, a], i) => (
                            <details
                                key={q}
                                className="group"
                                open={openFaq === i}
                                onToggle={(e) => setOpenFaq(e.currentTarget.open ? i : null)}
                            >
                                <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-4 text-base font-semibold text-ink [&::-webkit-details-marker]:hidden">
                                    <span>{q}</span>
                                    <svg className="h-5 w-5 shrink-0 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
                                        <path d="M6 9l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </summary>
                                <p className="max-w-prose pb-5 text-base leading-[1.7] text-ink-2">{a}</p>
                            </details>
                        ))}
                    </div>
                </div>
            </section>

            {/* Appel final */}
            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 sm:py-20">
                    <h2 className="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Proposer vos offres sur MALKUTHAR</h2>
                    <p className="mt-3 text-base text-ink-2">
                        Créez votre compte vendeur. Votre dossier est examiné sous 24 à 48 heures ouvrées.
                    </p>
                    <div className="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                        <Button variant="primary" href={primaryHref} native>
                            {isActive ? 'Accéder à mon tableau de bord' : 'Créer mon compte vendeur'}
                        </Button>
                        <Button variant="secondary" href={route('marketplace.vendor-terms')} native>
                            Lire les conditions vendeurs
                        </Button>
                    </div>
                </div>
            </section>
        </>
    );
}

Landing.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
