import { useState } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import Badge from '../../Components/ui/Badge';
import SectionHeading from '../../Components/ui/SectionHeading';
import Card from '../../Components/ui/Card';

const GRANDS_POLES = [
    {
        key: 'fashion_beauty',
        poleSlug: 'mode-beaute-cosmetiques',
        badge: 'Boutique & Créateurs',
        titre: 'Mode, Beauté & Cosmétiques',
        subtitle: 'Marques de cosmétiques naturels, créateurs de mode, maroquinerie d\'art & cordonnerie',
        photo: '/images/categories/physical.jpg',
        arguments: [
            {
                titre: 'Paiement garanti à la commande',
                texte: 'Fini les faux rendez-vous de livraison WhatsApp et les impayés. Les clients règlent via MoMo / Carte bancaire avant expédition.',
            },
            {
                titre: 'Force de vente affiliée intégrée',
                texte: 'Des centaines d\'affiliés actifs MALKUTHAR partagent vos articles avec leurs réseaux et touchent une commission fixée par l\'admin.',
            },
            {
                titre: 'Gestion poussée des stocks & variantes',
                texte: 'Gestion fluide des pointures, tailles de vêtements, contenances de sérums et alertes automatiques de réapprovisionnement.',
            },
        ],
    },
    {
        key: 'real_estate_hospitality',
        poleSlug: 'residences-guest-houses-immobilier',
        badge: 'Séjours & Immobilier',
        titre: 'Résidences, Guest Houses & Immo',
        subtitle: 'Guest houses de charme, résidences meublées de court séjour & agences immobilières agréées',
        photo: '/images/categories/residence.jpg',
        arguments: [
            {
                titre: 'Zéro commission prédatrice',
                texte: 'Évitez les 18 à 22 % ponctionnés par Airbnb et Booking. MALKUTHAR applique une commission juste et adaptée au continent.',
            },
            {
                titre: 'Clientèle d\'affaires & diaspora ciblée',
                texte: 'Accédez à une communauté engagée cherchant des séjours de standing vérifiés à Abidjan, Cotonou, Dakar et Lomé.',
            },
            {
                titre: 'Réservations & acomptes sécurisés',
                texte: 'Paiement ou acompte encaissé avant l\'arrivée du voyageur avec politique d\'annulation claire et traçabilité absolue.',
            },
        ],
    },
    {
        key: 'services_wellness',
        poleSlug: 'services-praticiens-specialises',
        badge: 'Consultations & Soins',
        titre: 'Services & Praticiens Spécialisés',
        subtitle: 'Thérapeutes holistiques, experts en bien-être, coachs certifiés & consultants d\'affaires',
        photo: '/images/categories/service.jpg',
        arguments: [
            {
                titre: 'Zéro "No-Show" garanti',
                texte: 'Le client choisit son créneau dans votre agenda et règle sa consultation en ligne. Votre temps est enfin protégé.',
            },
            {
                titre: 'Espace d\'autorité sanctuarisé',
                texte: 'Vos séances et bilans personnalisés disposent d\'un univers dédié, jamais mélangés avec de simples articles marchands.',
            },
            {
                titre: 'Consultations visio ou en cabinet',
                texte: 'Gestion automatique des liens de téléconsultation ou transmission sécurisée de l\'adresse de votre cabinet.',
            },
        ],
    },
    {
        key: 'digital_academy',
        poleSlug: 'savoirs-formations-en-ligne',
        badge: 'Académie & Savoirs',
        titre: 'Savoirs & Formations en Ligne',
        subtitle: 'Formateurs, auteurs d\'ebooks, conférenciers & maîtres d\'ateliers initiatiques',
        photo: '/images/categories/digital.jpg',
        arguments: [
            {
                titre: 'Livraison instantanée & anti-piratage',
                texte: 'Fichiers protégés et accès pédagogique débloqué immédiatement après paiement sans aucun coût logistique.',
            },
            {
                titre: 'Affiliation virale sur 3 niveaux',
                texte: 'Démultipliez vos ventes de formations grâce au réseau de parrainage et d\'affiliés rémunérés au succès.',
            },
            {
                titre: 'Encaissement multi-devises automatique',
                texte: 'Vendez vos formations aux apprenants d\'Afrique et du monde entier, recevez vos gains sur votre compte Mobile Money.',
            },
        ],
    },
];

const ETAPES = [
    {
        step: '01',
        titre: 'Candidature & choix de votre pôle',
        desc: 'Sélectionnez votre univers métier (Mode/Beauté, Hébergement/Immo, Services ou Académie) et présentez votre activité en 2 minutes.'
    },
    {
        step: '02',
        titre: 'Validation manuelle du compte (24h-48h)',
        desc: 'Notre équipe administrative vérifie l\'authenticité de chaque vendeur et agence pour garantir un catalogue d\'excellence.'
    },
    {
        step: '03',
        titre: 'Dépôt de vos offres & Modération stricte',
        desc: 'Publiez vos fiches produits, hébergements ou consultations. Chaque annonce est relue et validée par l\'admin avant d\'être visible.'
    },
    {
        step: '04',
        titre: 'Vendez et encaissez vos gains nets',
        desc: 'Vos offres sont diffusées auprès de milliers de membres et affiliés. Encaissez vos fonds sans risque d\'impayé par Mobile Money.'
    },
];

const FAQ = [
    [
        'Pourquoi les inscriptions et les produits sont-ils validés manuellement ?',
        'MALKUTHAR s\'engage à offrir un catalogue d\'excellence, d\'authenticité et de confiance. La vérification manuelle par notre équipe assure aux acheteurs des offres irréprochables et protège nos vendeurs partenaires contre la contrefaçon, la concurrence déloyale et les annonces frauduleuses.'
    ],
    [
        'Puis-je inscrire ma guest house ou mon agence immobilière sur MALKUTHAR ?',
        'Oui, absolument ! Le pôle Résidences, Guest Houses & Immobilier est spécialement conçu pour accueillir les hébergements meublés de court séjour et les agences agréées. Vos annonces disposent d\'une vitrine prestigieuse et de paiements d\'acomptes sécurisés, sans les commissions exorbitantes des géants américains.'
    ],
    [
        'Comment la séparation des univers ("Rien ne sera mélangé") est-elle assurée ?',
        'La plateforme segmente strictement les 4 grands pôles. Lorsqu\'un client recherche une guest house ou une consultation, il n\'est jamais distrait par des cosmétiques ou des vêtements. Chaque univers dispose de ses propres critères de recherche (nuitées, créneaux, tailles, etc.).'
    ],
    [
        'Combien de temps prend la validation de mon compte et de mes offres ?',
        'Notre équipe administrative traite les dossiers sous 24 à 48 heures ouvrées. Dès qu\'une décision est prise, vous recevez une notification par email et votre statut est mis à jour en temps réel.'
    ],
    [
        'Comment la commission est-elle calculée ?',
        'Une commission transparente est prélevée uniquement sur les ventes effectives. Aucun abonnement mensuel n\'est requis. L\'administrateur configure des pourcentages spécifiques avantageux selon les typologies d\'offres ou de produits.'
    ],
    [
        'Y a-t-il des frais fixes ou d\'abonnement ?',
        'Absolument aucun. Vous ne payez rien tant que vous ne vendez pas. Tout est inclus : fiches d\'offres enrichies, calendrier de prise de rendez-vous ou nuitées, encaissement sécurisé et support.'
    ],
    [
        'Comment et quand reçois-je mes versements ?',
        'Dès la commande expédiée, le séjour terminé ou la consultation effectuée, vos gains nets sont crédités sur votre solde vendeur disponible. Vous pouvez demander un versement par Mobile Money (Wave, MTN, Moov, Orange) ou virement bancaire.'
    ],
];

const fmt = (n) => {
    const s = Number(n).toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return s.replace('.', ',');
};

export default function Landing({ enabled, stats, starterRate, growthRate, growthThreshold, userVendor }) {
    const { auth } = usePage().props;
    const [openFaq, setOpenFaq] = useState(null);

    // Simulateur interactif de revenus
    const [unitPrice, setUnitPrice] = useState(15000);
    const [monthlySales, setMonthlySales] = useState(12);

    const rate = (starterRate || 15) / 100;
    const totalGross = Math.max(0, unitPrice) * Math.max(1, monthlySales);
    const totalFee = Math.round(totalGross * rate);
    const totalNet = totalGross - totalFee;

    return (
        <>
            <Head title="Vendre sur MALKUTHAR — Place de marché">
                <meta name="description" content="Ouvrez votre boutique sur MALKUTHAR : produits physiques, digitaux, consultations et formations. Inscription vendeur, sélection qualité et validation manuelle de chaque offre." />
            </Head>

            {/* Bandeau d'état personnalisé pour l'utilisateur connecté */}
            {auth?.user && (
                <section className="border-b border-accent/20 bg-accent-soft/40 py-3.5">
                    <div className="mx-auto flex max-w-[1180px] flex-wrap items-center justify-between gap-3 px-4 sm:px-6">
                        <div className="flex items-center gap-2.5 text-xs text-ink">
                            <span className="flex h-2 w-2 rounded-full bg-accent animate-pulse" />
                            {userVendor?.status === 'active' ? (
                                <p>
                                    Connecté en tant que vendeur : <strong className="font-semibold">{userVendor.displayName}</strong> (Boutique active).
                                </p>
                            ) : userVendor?.status === 'pending' ? (
                                <p>
                                    Votre demande pour la boutique <strong className="font-semibold">{userVendor.displayName}</strong> est <span className="font-semibold text-accent">en cours de validation manuelle par l'administrateur</span>.
                                </p>
                            ) : (
                                <p>
                                    Connecté en tant que <strong className="font-semibold">{auth.user.name}</strong>. Vous pouvez ouvrir votre boutique dès aujourd'hui.
                                </p>
                            )}
                        </div>
                        <div>
                            {userVendor?.status === 'active' ? (
                                <Button variant="primary" href={route('vendor.dashboard')} size="sm" native>
                                    Accéder à mon espace vendeur →
                                </Button>
                            ) : userVendor?.status === 'pending' ? (
                                <Button variant="secondary" href={route('vendor.application.status')} size="sm" native>
                                    Suivre ma candidature
                                </Button>
                            ) : (
                                <Button variant="primary" href={route('vendor.application')} size="sm" native>
                                    Candidater avec ce compte
                                </Button>
                            )}
                        </div>
                    </div>
                </section>
            )}

            {/* Hero Section */}
            <section className="relative overflow-hidden border-b border-line bg-gradient-to-b from-ground via-ground-alt/40 to-ground">
                <div className="mx-auto max-w-[1180px] px-4 pb-16 pt-12 sm:px-6 sm:pt-20 sm:pb-24">
                    <div className="inline-flex items-center gap-2 rounded-full border border-accent/30 bg-surface/80 px-3 py-1 shadow-xs backdrop-blur-md">
                        <span className="text-[10px] font-bold uppercase tracking-widest text-accent">Programme Partenaire Vendeur</span>
                        <span className="h-1 w-1 rounded-full bg-accent/60" />
                        <span className="text-xs font-medium text-ink-2">Sélection & Qualité</span>
                    </div>

                    <h1 className="mt-5 max-w-3xl font-display text-3xl font-bold leading-[1.12] tracking-[-0.015em] text-ink sm:text-5xl lg:text-6xl">
                        Déployez votre savoir-faire et vendez vos offres sur <span className="text-accent">MALKUTHAR</span>.
                    </h1>

                    <p className="mt-5 max-w-2xl text-base leading-relaxed text-ink-2 sm:text-lg">
                        Praticiens, créateurs, formateurs et artisans : ouvrez une boutique reconnue, bénéficiez d'un encaissement sécurisé et d'un réseau engagé. Zéro frais fixe, zéro abonnement — uniquement une commission transparente sur vos ventes réelles.
                    </p>

                    <div className="mt-8 flex flex-wrap items-center gap-4">
                        {auth?.user && userVendor?.status === 'active' ? (
                            <Button variant="primary" href={route('vendor.dashboard')} size="lg" native>
                                Accéder à mon espace vendeur
                            </Button>
                        ) : auth?.user && userVendor?.status === 'pending' ? (
                            <Button variant="primary" href={route('vendor.application.status')} size="lg" native>
                                Voir le statut de ma candidature
                            </Button>
                        ) : auth?.user ? (
                            <Button variant="primary" href={route('vendor.application')} size="lg" native>
                                Ouvrir ma boutique (Compte connecté)
                            </Button>
                        ) : (
                            <Button variant="primary" href={route('register.vendor')} size="lg" native>
                                Créer mon compte Vendeur
                            </Button>
                        )}

                        <Button variant="secondary" href={route('marketplace.shop')} size="lg">
                            Découvrir la place de marché
                        </Button>
                    </div>

                    {/* Garanties Modération Manuelle */}
                    <div className="mt-12 grid gap-3 sm:grid-cols-3">
                        <div className="flex items-center gap-3 rounded-xl border border-line bg-surface/80 p-3.5 shadow-xs">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </span>
                            <div>
                                <p className="text-xs font-bold text-ink">Vendeurs validés manuellement</p>
                                <p className="text-[11px] text-ink-3">Vérification humaine sous 24-48h</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-xl border border-line bg-surface/80 p-3.5 shadow-xs">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            </span>
                            <div>
                                <p className="text-xs font-bold text-ink">Produits relus & modérés</p>
                                <p className="text-[11px] text-ink-3">Chaque fiche approuvée par l'admin</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-xl border border-line bg-surface/80 p-3.5 shadow-xs">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <div>
                                <p className="text-xs font-bold text-ink">0 abonnement mensuel</p>
                                <p className="text-[11px] text-ink-3">Paiements & versements suivis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Chiffres clés */}
            {stats && (
                <section className="border-b border-line bg-ground-alt">
                    <div className="mx-auto grid max-w-[1180px] grid-cols-2 divide-x divide-line">
                        <div className="px-4 py-8 text-center sm:px-6">
                            <p className="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{stats.vendors}</p>
                            <p className="mt-1 text-xs font-medium uppercase tracking-wider text-ink-2">Boutique{stats.vendors > 1 ? 's' : ''} active{stats.vendors > 1 ? 's' : ''}</p>
                        </div>
                        <div className="px-4 py-8 text-center sm:px-6">
                            <p className="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{stats.offers}</p>
                            <p className="mt-1 text-xs font-medium uppercase tracking-wider text-ink-2">Offre{stats.offers > 1 ? 's' : ''} approuvée{stats.offers > 1 ? 's' : ''} en ligne</p>
                        </div>
                    </div>
                </section>
            )}

            {/* SECTION : LES 4 GRANDS PÔLES SECTORIELS (Rien n'est mélangé) */}
            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 lg:py-20">
                <div className="flex flex-col items-center text-center">
                    <span className="text-[11px] font-bold uppercase tracking-[0.2em] text-rust">
                        Espaces Spécifiques Dédiés
                    </span>
                    <h2 className="mt-2 font-display text-2xl font-bold tracking-tight text-ink sm:text-4xl">
                        Quatre Grands Pôles d'Excellence : Rien n'est mélangé
                    </h2>
                    <p className="mt-3 max-w-2xl text-sm leading-relaxed text-ink-2">
                        Sur MALKUTHAR, chaque secteur dispose de son propre univers sanctuarisé. Un acheteur cherchant une résidence ou un soin n'est jamais distrait par des vêtements ou cosmétiques. Chaque métier est valorisé avec ses critères précis.
                    </p>
                </div>

                <div className="mt-12 grid gap-8 md:grid-cols-2">
                    {GRANDS_POLES.map((pole) => (
                        <div
                            key={pole.key}
                            className="group flex flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-accent/50 hover:shadow-[0_16px_36px_-12px_rgba(46,44,40,0.12)]"
                        >
                            {/* Photo & Badge */}
                            <div className="relative h-48 w-full overflow-hidden bg-ground-alt sm:h-56">
                                <img
                                    src={pole.photo}
                                    alt={pole.titre}
                                    className="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/20 to-transparent" />
                                <div className="absolute bottom-4 left-4 right-4 flex items-end justify-between">
                                    <div>
                                        <span className="inline-block rounded-full bg-accent/90 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs backdrop-blur-md">
                                            {pole.badge}
                                        </span>
                                        <h3 className="mt-1.5 flex items-center gap-2 font-display text-xl font-bold text-white drop-shadow-sm">
                                            <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-white/20 text-white backdrop-blur-md">
                                                {pole.key === 'fashion_beauty' && (
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                    </svg>
                                                )}
                                                {pole.key === 'real_estate_hospitality' && (
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                    </svg>
                                                )}
                                                {pole.key === 'services_wellness' && (
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                )}
                                                {pole.key === 'digital_academy' && (
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
                                                    </svg>
                                                )}
                                            </span>
                                            <span>{pole.titre}</span>
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            {/* Body content */}
                            <div className="flex flex-1 flex-col p-6 sm:p-7">
                                <p className="text-xs font-semibold uppercase tracking-wider text-accent">
                                    {pole.subtitle}
                                </p>

                                {/* 3 Propositions Imbattables */}
                                <div className="mt-5 space-y-3.5 border-t border-line/70 pt-4">
                                    {pole.arguments.map((arg, idx) => (
                                        <div key={idx} className="flex items-start gap-2.5">
                                            <span className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-accent/15 text-[10px] font-bold text-accent">
                                                ✓
                                            </span>
                                            <div>
                                                <p className="text-xs font-bold text-ink">{arg.titre}</p>
                                                <p className="mt-0.5 text-[11px] leading-relaxed text-ink-3">{arg.texte}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Action Buttons */}
                                <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-line/60 pt-4">
                                    <Button
                                        variant="primary"
                                        size="sm"
                                        href={auth?.user ? (userVendor?.status === 'active' ? route('vendor.dashboard') : route('vendor.application')) : route('register.vendor')}
                                        native
                                    >
                                        Postuler dans ce pôle →
                                    </Button>
                                    <Link
                                        href={route('marketplace.shop', { pole: pole.poleSlug })}
                                        className="inline-flex items-center text-xs font-semibold text-ink-2 hover:text-accent transition-colors"
                                    >
                                        Explorer l'univers vitrine
                                        <svg className="ml-1 h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor">
                                            <path fillRule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* SECTION : 4 ARGUMENTS IMBATTABLES FACE AUX AUTRES CANAUX */}
            <section className="border-y border-line bg-ground-alt/70">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 lg:py-20">
                    <SectionHeading
                        eyebrow="Avantage Concurrentiel"
                        title="Pourquoi vendre sur MALKUTHAR plutôt que sur WhatsApp ou Instagram ?"
                    />
                    <p className="max-w-2xl text-sm text-ink-2">
                        Vendre sur les réseaux sociaux vous expose aux faux clients, aux no-shows et aux commissions cachées des plateformes internationales. MALKUTHAR vous apporte la sécurité d'une institution.
                    </p>

                    <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="rounded-2xl border border-line bg-surface p-6 shadow-xs">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                            <h3 className="mt-4 font-display text-sm font-bold text-ink">Pré-paiement garanti à 100%</h3>
                            <p className="mt-2 text-xs leading-relaxed text-ink-2">
                                Les clients paient d'avance par Mobile Money (Wave, MTN, Orange, Moov) ou Carte. Fini les livreurs qui se déplacent pour rien.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-line bg-surface p-6 shadow-xs">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                            <h3 className="mt-4 font-display text-sm font-bold text-ink">Armée d'affiliés vendeurs</h3>
                            <p className="mt-2 text-xs leading-relaxed text-ink-2">
                                Bénéficiez automatiquement du réseau d'affiliés MALKUTHAR. Ils partagent vos offres et vous amènent des clients sans budget publicitaire.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-line bg-surface p-6 shadow-xs">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                            </span>
                            <h3 className="mt-4 font-display text-sm font-bold text-ink">Zéro commission abusive</h3>
                            <p className="mt-2 text-xs leading-relaxed text-ink-2">
                                Pas de ponction de 20% façon Airbnb ou Booking. Une commission mesurée, transparente, sans aucun abonnement mensuel.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-line bg-surface p-6 shadow-xs">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                            <h3 className="mt-4 font-display text-sm font-bold text-ink">Label Vendeur Certifié</h3>
                            <p className="mt-2 text-xs leading-relaxed text-ink-2">
                                La validation manuelle stricte par notre administration élimine les escrocs et confère une crédibilité immédiate à votre marque.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Parcours Vendeur & Étapes avec Focus Modération */}
            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 lg:py-20">
                <SectionHeading eyebrow="Processus transparent" title="Comment devenir vendeur en 4 étapes" />

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {ETAPES.map((etape) => (
                        <div key={etape.step} className="relative rounded-2xl border border-line bg-surface p-6">
                            <span className="font-display text-2xl font-black text-accent/50">{etape.step}</span>
                            <h3 className="mt-2 font-display text-sm font-bold text-ink">{etape.titre}</h3>
                            <p className="mt-2 text-xs leading-relaxed text-ink-2">{etape.desc}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Simulateur Interactif de Gains Vendeur */}
            <section className="border-y border-line bg-gradient-to-br from-surface via-accent-soft/20 to-surface">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 lg:py-20">
                    <div className="grid gap-10 lg:grid-cols-[1.1fr_1fr] lg:items-center">
                        <div>
                            <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Simulateur de revenus</p>
                            <h2 className="mt-2 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-4xl">
                                Calculez vos gains nets en temps réel
                            </h2>
                            <p className="mt-3 text-sm leading-relaxed text-ink-2">
                                Chez MALKUTHAR, aucun frais caché ni abonnement mensuel : vous ne payez une commission que lorsque vous encaissez. L'administrateur peut également ajuster des pourcentages spécifiques par produit selon la catégorie.
                            </p>

                            <div className="mt-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-ink">
                                        Prix de vente unitaire (FCFA)
                                    </label>
                                    <input
                                        type="number"
                                        min="500"
                                        step="500"
                                        value={unitPrice}
                                        onChange={(e) => setUnitPrice(Number(e.target.value))}
                                        className="mt-1.5 h-11 w-full max-w-xs rounded-xl border border-line bg-surface px-4 text-sm font-semibold text-ink shadow-xs focus:border-accent focus:ring-2 focus:ring-accent/20"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-ink">
                                        Estimation de ventes par mois
                                    </label>
                                    <div className="mt-1.5 flex items-center gap-4">
                                        <input
                                            type="range"
                                            min="1"
                                            max="100"
                                            value={monthlySales}
                                            onChange={(e) => setMonthlySales(Number(e.target.value))}
                                            className="h-2 w-full max-w-xs accent-accent"
                                        />
                                        <span className="font-display text-sm font-bold text-ink">{monthlySales} ventes</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Carte de résultat du simulateur */}
                        <div className="rounded-3xl border border-accent/30 bg-surface p-7 shadow-[0_16px_36px_-12px_rgba(138,109,44,0.15)]">
                            <Badge tone="gold">Bilan estimatif mensuel</Badge>

                            <div className="mt-5 divide-y divide-line/70">
                                <div className="flex items-center justify-between pb-3">
                                    <span className="text-xs text-ink-2">Chiffre d'affaires brut généré</span>
                                    <span className="font-display text-sm font-bold text-ink">{totalGross.toLocaleString('fr-FR')} FCFA</span>
                                </div>

                                <div className="flex items-center justify-between py-3">
                                    <span className="text-xs text-ink-2">
                                        Commission plateforme indicative ({fmt(rate * 100)} %)
                                    </span>
                                    <span className="font-display text-sm font-semibold text-ink-3">- {totalFee.toLocaleString('fr-FR')} FCFA</span>
                                </div>

                                <div className="pt-4">
                                    <span className="block text-xs font-bold uppercase tracking-wider text-accent">Votre revenu net vendeur</span>
                                    <p className="mt-1 font-display text-3xl font-extrabold text-accent sm:text-4xl">
                                        {totalNet.toLocaleString('fr-FR')} <span className="text-base font-bold text-ink">FCFA</span>
                                    </p>
                                    <p className="mt-2 text-[11px] text-ink-3 leading-snug">
                                        Montant net crédité directement sur votre solde vendeur, disponible pour retrait Mobile Money ou virement.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-6 border-t border-line/60 pt-5">
                                <Button
                                    variant="primary"
                                    className="w-full justify-center"
                                    href={auth?.user ? (userVendor?.status === 'active' ? route('vendor.dashboard') : route('vendor.application')) : route('register.vendor')}
                                    native
                                >
                                    {auth?.user && userVendor?.status === 'active' ? 'Accéder à mon espace' : 'Commencer à vendre maintenant'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* FAQ */}
            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 lg:py-20">
                <SectionHeading eyebrow="Questions fréquentes" title="Tout ce que vous devez savoir avant de postuler" />

                <div className="mt-8 divide-y divide-line rounded-2xl border border-line bg-surface">
                    {FAQ.map(([q, a], i) => (
                        <details
                            key={q}
                            className="group px-6 py-5 cursor-pointer"
                            open={openFaq === i}
                            onToggle={(e) => setOpenFaq(e.currentTarget.open ? i : null)}
                        >
                            <summary className="flex list-none items-center justify-between gap-4 font-display text-base font-semibold text-ink">
                                <span>{q}</span>
                                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ground-alt text-ink-3 transition-transform group-open:rotate-45">
                                    +
                                </span>
                            </summary>
                            <p className="mt-3 text-xs leading-relaxed text-ink-2 sm:text-sm">
                                {a}
                            </p>
                        </details>
                    ))}
                </div>
            </section>

            {/* CTA Final */}
            <section className="border-t border-line bg-ground-alt/60 py-16 text-center sm:py-20">
                <div className="mx-auto max-w-2xl px-4 sm:px-6">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Rejoignez l'élite des créateurs</p>
                    <h2 className="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-4xl">
                        Prêt à faire rayonner vos offres ?
                    </h2>
                    <p className="mt-3 text-sm text-ink-2">
                        Créez votre compte vendeur dès aujourd'hui. Notre équipe administrative valide votre compte manuellement sous 24 à 48h.
                    </p>

                    <div className="mt-7 flex flex-wrap justify-center gap-3">
                        <Button
                            variant="primary"
                            size="lg"
                            href={auth?.user ? (userVendor?.status === 'active' ? route('vendor.dashboard') : route('vendor.application')) : route('register.vendor')}
                            native
                        >
                            {auth?.user && userVendor?.status === 'active' ? 'Accéder à mon tableau de bord' : 'Créer mon compte Vendeur'}
                        </Button>
                        <Button variant="secondary" size="lg" href={route('marketplace.vendor-terms')}>
                            Lire les conditions vendeurs
                        </Button>
                    </div>
                </div>
            </section>
        </>
    );
}

Landing.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
