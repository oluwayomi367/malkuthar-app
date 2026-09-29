import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import SectionHeading from '../Components/ui/SectionHeading';
import Reassurance from '../Components/commerce/Reassurance';

const TESTIMONIALS = [
    { quote: "J'ai reçu la formation quelques minutes après le paiement. Tout était clair, du panier au téléchargement.", name: 'Aïcha D.', role: 'Cliente · Cotonou' },
    { quote: "J'ai partagé mon lien dans mon cercle et touché mes premiers retraits en Mobile Money le mois suivant.", name: 'Komla A.', role: 'Affilié · Lomé' },
    { quote: "J'ai réservé une séance en visio : le créneau proposé était réel, la confirmation immédiate.", name: 'Sarah M.', role: 'Cliente · Abidjan' },
    { quote: "Le coffret est arrivé suivi jusqu'à ma porte. Enfin une maison qui réunit les livres, les cours et les séances.", name: 'Ibrahim T.', role: 'Client · Ouagadougou' },
    { quote: "Interface claire, paiement local sans friction. J'ai recommandé la plateforme à tout mon groupe de lecture.", name: 'Ngozi E.', role: 'Cliente · Douala' },
    { quote: "En tant qu'auteur, je publie mes ebooks et je suis payé dès la vente. La gestion est transparente.", name: 'Yao K.', role: 'Vendeur · Kara' },
];

const AFFILIATION_STEPS = [
    { step: 1, title: 'Activez votre espace', text: 'En un clic depuis votre compte. Aucun second compte à créer.' },
    { step: 2, title: 'Partagez votre lien', text: 'Votre lien de parrainage personnel, sur vos réseaux ou messageries.' },
    { step: 3, title: 'La vente est attribuée', text: 'Un achat via votre lien sur un produit éligible vous est rattaché.' },
    { step: 4, title: 'La commission tombe', text: 'Créditée dès le paiement, retirable par Mobile Money ou virement.' },
];

const DEFAULT_CATEGORIES = [
    {
        type: 'digital',
        label: 'Savoirs & Formations',
        short: 'Ebooks · Formations · Audios',
        text: "Vos écrits, cours initiatiques et méditations guidées, livrés à l'instant dès le paiement confirmé — sans stock ni expédition.",
        icon: '<path d="M10 3v10m0 0l-3.5-3.5M10 13l3.5-3.5M4 15v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1"/>',
        image: 'images/categories/digital.jpg',
        color: '#b0892f',
        badge: 'Téléchargement instantané',
        destination: '/boutique?type=digital',
        highlights: [
            'Accès immédiat et illimité dès validation du paiement en ligne',
            'Lecture optimale sur smartphone, tablette, liseuse et ordinateur',
            'Éligible au programme d’affiliation à 3 niveaux (jusqu’à 30 %)',
        ],
    },
    {
        type: 'physical',
        label: "Mode & Artisanat d'art",
        short: "Livres d'art · Maroquinerie · Accessoires",
        text: "Livres imprimés d’art, maroquinerie noble, coffrets précieux et créations artisanales — gestion soignée du stock et expédition suivie jusqu’à votre porte.",
        icon: '<path d="M3 6l7-3 7 3v8l-7 3-7-3V6z"/><path d="M3 6l7 3 7-3M10 9v9"/>',
        image: 'images/categories/physical.jpg',
        color: '#b4633c',
        badge: 'Expédition soignée & suivie',
        destination: '/boutique?type=physical',
        highlights: [
            'Ouvrages de collection & créations artisanales d’exception',
            'Suivi d’expédition en temps réel par notification e-mail / SMS',
            'Conditionnement protecteur haut de gamme pour chaque commande',
        ],
    },
    {
        type: 'residence',
        label: 'Résidences & Guest houses',
        short: "Séjours d'exception · Domaines · Immersion",
        text: "Sélection rigoureuse de guest houses, lodges et demeures privées pour vos retraites intérieures, séjours d'inspiration et temps de repos ressourçants.",
        icon: '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        image: 'images/categories/residence.jpg',
        color: '#8a6d2c',
        badge: "Lieux d'exception certifiés",
        destination: '/place-de-marche?pole=residences-guest-houses-immobilier',
        highlights: [
            'Demeures calmes, sécurisées et sélectionnées avec la plus grande exigence',
            'Réservation directe et transparente sans intermédiaires cachés',
            'Cadres privilégiés pour retraites spirituelles, création et repos profond',
        ],
    },
    {
        type: 'service',
        label: 'Soins & Praticiens',
        short: 'Consultations · Séances · Bilans',
        text: "Consultations individuelles, bilans et séances d'accompagnement : réservez un créneau direct selon vos disponibilités, en visioconférence ou en présentiel.",
        icon: '<rect x="3.5" y="4.5" width="13" height="12" rx="2"/><path d="M3.5 8h13M7 3v3M13 3v3"/>',
        image: 'images/categories/service.jpg',
        color: '#5f7355',
        badge: 'Réservation directe en temps réel',
        destination: '/boutique?type=service',
        highlights: [
            'Prise de rendez-vous synchronisée sur agenda en direct',
            'Entretiens personnalisés dispensés par des praticiens et experts certifiés',
            'Séance en visioconférence confidentielle ou en cabinet partenaire',
        ],
    },
];

const VALUES = [
    {
        title: 'Sélection & Rigueur',
        desc: 'Chaque œuvre, création artisanale, formation ou résidence fait l’objet d’une validation manuelle rigoureuse.',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    },
    {
        title: 'Paiements Sécurisés',
        desc: 'Réglez en toute sérénité par Mobile Money (Wave, MTN, Moov, Orange) ou cartes bancaires VISA / Mastercard.',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    },
    {
        title: 'Transparence Absolue',
        desc: 'Zéro coût caché, attribution transparente des commissions et suivi d’expédition notifié en temps réel.',
        icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Conciergerie Dédiée',
        desc: 'Une assistance humaine disponible pour vous conseiller, guider vos réservations et accompagner vos commandes.',
        icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
    },
];

function CategoryTabs({ categories: propCategories }) {
    const categories = useMemo(() => {
        return DEFAULT_CATEGORIES
            .filter((def) => def.type !== 'residence')
            .map((def) => {
                const prop = (propCategories || []).find((p) => p.type === def.type) || {};
                return { ...def, ...prop };
            });
    }, [propCategories]);

    const [tab, setTab] = useState(categories[0]?.type || 'digital');
    const active = categories.find((c) => c.type === tab) || categories[0];

    return (
        <div className="mt-8">
            {/* Onglets sélecteurs */}
            <div className="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                <div className="flex min-w-max items-center justify-center gap-2 rounded-2xl bg-ground-alt/70 p-1.5 border border-line sm:min-w-0">
                    {categories.map((c) => {
                        const isSelected = tab === c.type;
                        return (
                            <button
                                key={c.type}
                                type="button"
                                onClick={() => setTab(c.type)}
                                aria-pressed={isSelected}
                                className={[
                                    'inline-flex flex-shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-xs sm:text-sm font-semibold transition-all duration-200',
                                    isSelected
                                        ? 'bg-surface text-ink shadow-2xs border border-line-strong'
                                        : 'text-ink-2 hover:text-ink hover:bg-surface/60 border border-transparent',
                                ].join(' ')}
                            >
                                <span
                                    className="inline-flex h-6 w-6 items-center justify-center rounded-lg text-white shadow-2xs transition-transform duration-200"
                                    style={{ backgroundColor: c.color }}
                                >
                                    <svg
                                        className="h-3.5 w-3.5"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.75"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        dangerouslySetInnerHTML={{ __html: c.icon }}
                                    />
                                </span>
                                <span>{c.label}</span>
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Carte de contenu principale */}
            <div className="mt-6 overflow-hidden rounded-3xl border border-line bg-surface p-6 sm:p-8 lg:p-10 shadow-xs">
                {active && (
                    <div className="grid gap-8 lg:grid-cols-12 lg:items-center">
                        {/* Présentation textuelle */}
                        <div className="order-2 space-y-5 lg:order-1 lg:col-span-7">
                            <div className="flex flex-wrap items-center gap-2">
                                <span
                                    className="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold tracking-wide"
                                    style={{
                                        backgroundColor: `${active.color}15`,
                                        color: active.color,
                                        borderColor: `${active.color}35`,
                                        borderWidth: '1px',
                                    }}
                                >
                                    <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: active.color }} />
                                    {active.short}
                                </span>
                            </div>

                            <div>
                                <h3 className="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                                    {active.label}
                                </h3>
                                <p className="mt-3 text-sm sm:text-base text-ink-2 leading-relaxed">
                                    {active.text}
                                </p>
                            </div>

                            {active.highlights && (
                                <ul className="space-y-2.5 pt-1 text-xs sm:text-sm text-ink-2">
                                    {active.highlights.map((point, idx) => (
                                        <li key={idx} className="flex items-start gap-2.5">
                                            <span
                                                className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full"
                                                style={{
                                                    backgroundColor: `${active.color}22`,
                                                    color: active.color,
                                                }}
                                            >
                                                <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                    <path d="M2.5 6.5l2.5 2.5 4.5-5" />
                                                </svg>
                                            </span>
                                            <span>{point}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}

                            <div className="pt-3 flex flex-wrap items-center gap-3">
                                <Button
                                    variant="primary"
                                    href={active.destination || route('catalog.index', { type: active.type })}
                                    className="h-10 px-5 text-xs sm:text-sm font-semibold shadow-xs"
                                >
                                    Explorer les {active.label.toLowerCase()} →
                                </Button>
                                <Button
                                    variant="secondary"
                                    href={route('marketplace.shop')}
                                    className="h-10 px-4 text-xs sm:text-sm font-medium"
                                >
                                    Place de marché
                                </Button>
                            </div>
                        </div>

                        {/* Visuel immersif adapté à chaque onglet */}
                        <div className="relative order-1 lg:order-2 lg:col-span-5">
                            <div className="group relative aspect-[4/3] w-full overflow-hidden rounded-2xl border border-line bg-ground-alt shadow-sm">
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-line-strong">
                                    <svg
                                        className="h-16 w-16 opacity-30"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        dangerouslySetInnerHTML={{ __html: active.icon }}
                                    />
                                    <span className="text-xs text-ink-3 opacity-40">{active.label}</span>
                                </div>

                                <img
                                    key={active.type}
                                    src={`/${active.image}`}
                                    alt={`Aperçu — ${active.label}`}
                                    loading="lazy"
                                    onError={(e) => {
                                        e.currentTarget.style.opacity = '0';
                                    }}
                                    className="relative h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                />

                                <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent" />

                                <div className="absolute bottom-3.5 left-3.5 sm:bottom-4 sm:left-4">
                                    <span className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/60 px-3 py-1.5 text-[12px] font-medium text-white shadow-lg backdrop-blur-md">
                                        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: active.color }} />
                                        {active.badge}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

function TestimonialsSlider() {
    const [perPage, setPerPage] = useState(3);
    const [page, setPage] = useState(0);
    const pages = Math.max(1, Math.ceil(TESTIMONIALS.length / perPage));

    useEffect(() => {
        const sync = () => {
            const w = window.innerWidth;
            setPerPage(w < 640 ? 1 : w < 1024 ? 2 : 3);
        };
        sync();
        window.addEventListener('resize', sync);
        return () => window.removeEventListener('resize', sync);
    }, []);

    useEffect(() => {
        if (page > pages - 1) setPage(pages - 1);
    }, [pages, page]);

    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const t = setInterval(() => setPage((p) => (p + 1) % pages), 7000);
        return () => clearInterval(t);
    }, [pages]);

    const slices = useMemo(() => Array.from({ length: pages }, (_, p) => TESTIMONIALS.slice(p * perPage, p * perPage + perPage)), [pages, perPage]);

    return (
        <div className="mt-6">
            <div className="overflow-hidden">
                <div className="flex transition-transform duration-500 ease-out" style={{ transform: `translateX(-${page * 100}%)` }}>
                    {slices.map((items, p) => (
                        <div key={p} className="w-full shrink-0">
                            <div className="grid gap-4 pb-1 sm:grid-cols-2 lg:grid-cols-3">
                                {items.map((t, idx) => (
                                    <figure key={idx} className="flex h-full flex-col rounded-2xl border border-line bg-surface p-5 sm:p-6 shadow-2xs">
                                        <div className="flex items-center gap-1 text-amber-500 text-xs">
                                            {'★★★★★'.split('').map((s, i) => (
                                                <span key={i}>{s}</span>
                                            ))}
                                        </div>
                                        <blockquote className="mt-3 flex-1 text-sm sm:text-base leading-relaxed text-ink font-medium">« {t.quote} »</blockquote>
                                        <figcaption className="mt-4 pt-3 border-t border-line/60 text-xs">
                                            <span className="font-bold text-ink">{t.name}</span>
                                            <span className="text-ink-3"> — </span>
                                            <span className="text-ink-2">{t.role}</span>
                                        </figcaption>
                                    </figure>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="mt-5 flex items-center justify-between">
                <div className="flex gap-1.5">
                    {Array.from({ length: pages }, (_, p) => (
                        <button
                            key={p}
                            type="button"
                            onClick={() => setPage(p)}
                            aria-label={`Page ${p + 1}`}
                            className={['h-1.5 rounded-full transition-all', page === p ? 'w-5 bg-accent' : 'w-1.5 bg-line-strong hover:bg-ink-3'].join(' ')}
                        />
                    ))}
                </div>
                <div className="flex gap-1">
                    <button
                        type="button"
                        onClick={() => setPage((p) => (p - 1 + pages) % pages)}
                        aria-label="Précédent"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink"
                    >
                        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M10 3L5 8l5 5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    </button>
                    <button
                        type="button"
                        onClick={() => setPage((p) => (p + 1) % pages)}
                        aria-label="Suivant"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink"
                    >
                        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M6 3l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Home() {
    const { categories = [], marketplaceEnabled } = usePage().props;

    const stats = [
        { num: '4 Pôles', label: 'Univers d’excellence', desc: 'Savoirs, Mode, Résidences & Soins' },
        { num: '24/7', label: 'Livraison instantanée', desc: 'Téléchargements immédiats après paiement' },
        { num: '3 Niveaux', label: 'Affiliation transparente', desc: '15 % · 10 % · 5 % sur chaque vente' },
        { num: '100 % Sûr', label: 'Paiements vérifiés', desc: 'Mobile Money local & cartes bancaires' },
    ];

    return (
        <>
            <Head title="MALKUTHAR — Maison d'Excellence & Place de Marché">
                <meta
                    name="description"
                    content="MALKUTHAR réunit quatre grands métiers : savoirs et formations, mode et maroquinerie, résidences d'exception et soins spécialisés. Programme d'affiliation sur 3 niveaux."
                />
            </Head>

            {/* BANNIÈRE D'ACCUEIL D'ORIGINE — ÉLÉGANCE, SÉRÉNITÉ & HUMANITÉ */}
            <section className="relative overflow-hidden border-b border-line/60 bg-gradient-to-b from-ground via-ground to-ground-alt/40">
                <div className="relative mx-auto max-w-[1180px] px-4 pb-16 pt-14 text-center sm:px-6 sm:pb-20 sm:pt-20">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">MALKUTHAR SARL</p>
                    <h1 className="mx-auto mt-4 max-w-3xl font-display text-[clamp(2.1rem,5.5vw,3.8rem)] font-bold leading-[1.12] tracking-[-0.018em] text-ink">
                        Transmettez vos savoirs, vos œuvres, <span className="quote font-normal text-accent italic">votre présence.</span>
                    </h1>
                    <p className="mx-auto mt-5 max-w-2xl text-base sm:text-lg text-ink-2 leading-relaxed">
                        Une maison d'excellence : vos formations et écrits numériques livrés à l'instant, vos créations artisanales et maroquinerie expédiées avec soin, vos séjours en résidences d'exception et vos consultations avec nos praticiens.
                    </p>
                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Button variant="primary" href={route('catalog.index')} className="h-11 px-7 text-sm font-semibold shadow-xs">
                            Explorer la boutique
                        </Button>
                        <Button variant="secondary" href={route('marketplace.shop')} className="h-11 px-6 text-sm font-medium">
                            Place de marché
                        </Button>
                        <Button variant="ghost" href={route('affiliation')} className="h-11 px-5 text-sm font-medium text-accent hover:text-accent-2">
                            Devenir affilié →
                        </Button>
                    </div>
                    <p className="mt-6 text-xs text-ink-3">
                        Livraison numérique immédiate 24/7 · Expéditions suivies · Paiements sécurisés Mobile Money & Cartes
                    </p>
                </div>
            </section>

            {/* RESTE DU CONTENU DE LA PAGE */}
            <div className="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 sm:py-14 space-y-14 sm:space-y-20">

                {/* 2. BANDEAU DE STATISTIQUES & IMPACT */}
                <section className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    {stats.map((s) => (
                        <div key={s.num} className="rounded-2xl border border-line bg-surface p-4 sm:p-5 shadow-2xs hover:border-line-strong transition-colors">
                            <p className="font-display text-xl sm:text-2xl font-extrabold text-accent">
                                {s.num}
                            </p>
                            <p className="mt-1 text-xs font-bold text-ink">
                                {s.label}
                            </p>
                            <p className="mt-0.5 text-[11px] sm:text-xs text-ink-2 leading-tight">
                                {s.desc}
                            </p>
                        </div>
                    ))}
                </section>

                {/* 3. LES TROIS UNIVERS — TABS INTERACTIFS AVEC PHOTOS ET DÉTAILS */}
                <section>
                    <SectionHeading
                        eyebrow="Trois voies, une maison"
                        title="Par où voulez-vous entrer ?"
                        description="Explorez nos univers dédiés : du savoir numérique à l'artisanat d'art noble, jusqu'aux soins et consultations personnalisés."
                    />
                    <CategoryTabs categories={categories} />
                </section>

                {/* 4. LES 4 PILIERS EN ACCÈS DIRECT — GRILLE VISUELLE */}
                <section className="space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                                Métiers de la maison
                            </span>
                            <h2 className="mt-2 font-display text-xl sm:text-2xl font-bold tracking-tight text-ink">
                                Les 4 piliers fondateurs
                            </h2>
                        </div>
                        <Link href={route('catalog.index')} className="text-xs font-semibold text-accent hover:text-accent-2 transition-colors">
                            Voir toutes les catégories →
                        </Link>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {DEFAULT_CATEGORIES.map((cat) => (
                            <Link
                                key={cat.type}
                                href={cat.destination}
                                className="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-2xs transition-all duration-300 hover:border-accent/60 hover:shadow-sm"
                            >
                                <div className="relative aspect-[16/10] w-full overflow-hidden bg-ground-alt">
                                    <img
                                        src={`/${cat.image}`}
                                        alt={cat.label}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                                    />
                                    <div className="absolute top-2.5 left-2.5">
                                        <span className="inline-flex items-center rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm backdrop-blur-md">
                                            {cat.badge}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex flex-1 flex-col p-4 sm:p-5">
                                    <div className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: cat.color }} />
                                        <span className="text-[11px] font-bold uppercase tracking-wider text-ink-3">
                                            {cat.short.split('·')[0].trim()}
                                        </span>
                                    </div>
                                    <h3 className="mt-1.5 font-display text-base font-bold text-ink group-hover:text-accent transition-colors">
                                        {cat.label}
                                    </h3>
                                    <p className="mt-1.5 flex-1 text-xs text-ink-2 leading-relaxed line-clamp-2">
                                        {cat.text}
                                    </p>
                                    <div className="mt-4 flex items-center gap-1.5 text-xs font-bold text-accent group-hover:translate-x-0.5 transition-transform">
                                        <span>Découvrir</span>
                                        <span>→</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                {/* 5. PLACE DE MARCHÉ MULTI-VENDEURS & CRÉATEURS */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-accent/30 bg-accent-soft/70 px-3 py-1 text-[11px] font-semibold tracking-wider uppercase text-accent">
                            <span className="h-1.5 w-1.5 rounded-full bg-accent" />
                            {marketplaceEnabled ? 'Place de marché en ligne' : 'Bientôt disponible'}
                        </span>
                    </div>
                    <h2 className="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                        Une place de marché ouverte aux créateurs, hôtes & praticiens
                    </h2>
                    <p className="mt-3 max-w-2xl text-xs sm:text-sm text-ink-2 leading-relaxed">
                        Praticiens certifiés, formateurs renommés, artisans d'art et gérants de demeures d'exception vendent sur MALKUTHAR sous leur propre nom : produits physiques, formations, consultations et séjours, avec un standard de qualité rigoureusement audité.
                    </p>

                    <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            { name: 'Mode, Beauté & Cosmétiques', desc: 'Maroquinerie, textiles nobles & soins naturels', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
                            { name: 'Résidences & Guest Houses', desc: 'Lodges, demeures & retraites d’inspiration', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
                            { name: 'Soins & Praticiens Spécialisés', desc: 'Consultations en direct & accompagnement', icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
                            { name: 'Savoirs & Formations en ligne', desc: 'Ebooks, cours initiatiques & fichiers audio', icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5' },
                        ].map((p) => (
                            <div key={p.name} className="flex flex-col rounded-xl border border-line bg-surface p-4 shadow-2xs">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-accent">
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d={p.icon} />
                                        </svg>
                                    </span>
                                    <span className="text-xs font-bold text-ink leading-tight">{p.name}</span>
                                </div>
                                <p className="mt-2 text-[11px] text-ink-2 leading-relaxed">{p.desc}</p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-7 flex flex-wrap gap-3">
                        <Button variant="primary" href={route('marketplace.shop')} className="h-10 px-5 text-xs sm:text-sm font-semibold shadow-xs">
                            Découvrir la place de marché →
                        </Button>
                        <Button variant="secondary" href={route('marketplace')} className="h-10 px-4 text-xs sm:text-sm font-medium">
                            Vendre sur MALKUTHAR
                        </Button>
                    </div>
                </section>

                {/* 6. PROGRAMME D'AFFILIATION AVEC VISUEL & CALCULATEUR RAPIDE */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-surface p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
                        <div className="lg:col-span-7">
                            <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                                Rémunération Partenaire
                            </span>
                            <h2 className="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                                Gagnez en partageant sur 3 niveaux de commissions
                            </h2>
                            <p className="mt-3 text-xs sm:text-sm text-ink-2 leading-relaxed max-w-xl">
                                Recommandez les créations et formations digitales : touchez <span className="font-bold text-ink">15 % · 10 % · 5 %</span> sur vos ventes directes et sur toute votre lignée de filleuls, avec paiement automatisé.
                            </p>

                            <ol className="mt-6 grid gap-4 sm:grid-cols-2">
                                {AFFILIATION_STEPS.map((s) => (
                                    <li key={s.step} className="flex gap-2.5">
                                        <span className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent/15 text-xs font-bold text-accent">
                                            {s.step}
                                        </span>
                                        <div>
                                            <p className="text-xs font-bold text-ink">{s.title}</p>
                                            <p className="mt-0.5 text-[11px] leading-tight text-ink-2">{s.text}</p>
                                        </div>
                                    </li>
                                ))}
                            </ol>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <Button variant="primary" href={route('register')} native className="h-10 px-5 text-xs font-semibold shadow-xs">
                                    Rejoindre le programme
                                </Button>
                                <Button variant="ghost" href={route('affiliation')} className="h-10 px-4 text-xs font-medium">
                                    Comment ça marche →
                                </Button>
                            </div>
                        </div>

                        <div className="relative lg:col-span-5 overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src="/images/affiliation-hero.jpg"
                                alt="Programme affilié partenaire MALKUTHAR"
                                className="h-60 sm:h-72 w-full object-cover object-center"
                            />
                            <div className="absolute bottom-3 left-3 right-3 rounded-xl bg-black/60 p-3 text-white backdrop-blur-md">
                                <div className="flex items-center justify-between text-xs font-semibold">
                                    <span>Commissions partagées</span>
                                    <span className="text-accent font-bold">15 % + 10 % + 5 %</span>
                                </div>
                                <p className="mt-1 text-[11px] text-white/80">Retraits instantanés via Mobile Money ou virement bancaire.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 7. VALEURS & ENGAGEMENTS DE LA MAISON */}
                <section className="space-y-6">
                    <SectionHeading
                        eyebrow="Exigence & Confiance"
                        title="Pourquoi choisir la maison MALKUTHAR"
                        description="Une plateforme conçue pour allier excellence artisanale, transmission rigoureuse et transactions sécurisées."
                    />
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {VALUES.map((val) => (
                            <div key={val.title} className="rounded-2xl border border-line bg-surface p-5 sm:p-6 shadow-2xs hover:border-line-strong transition-all duration-200">
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent/15 text-accent">
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                        <path strokeLinecap="round" strokeLinejoin="round" d={val.icon} />
                                    </svg>
                                </span>
                                <h3 className="mt-4 font-display text-base font-bold text-ink">
                                    {val.title}
                                </h3>
                                <p className="mt-2 text-xs sm:text-sm text-ink-2 leading-relaxed">
                                    {val.desc}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 8. TÉMOIGNAGES CLIENTS & AVIS VÉRIFIÉS */}
                <section>
                    <SectionHeading
                        eyebrow="Ils nous font confiance"
                        title="Ce qu'en disent nos clients, créateurs & partenaires"
                        description="Découvrez les retours d'expérience de notre communauté à travers l'Afrique et la diaspora."
                    />
                    <TestimonialsSlider />
                </section>

                {/* 9. RÉASSURANCE & CERTIFICATION */}
                <section className="rounded-2xl border border-line bg-surface p-6 sm:p-8 shadow-2xs">
                    <Reassurance />
                </section>

                {/* 10. CTA FINAL AVEC FOND ACCROCHEUR */}
                <section className="rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-ink text-white p-8 sm:p-12 text-center shadow-lg">
                    <h2 className="font-display text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Explorez l'univers complet de la maison MALKUTHAR
                    </h2>
                    <p className="mx-auto mt-2 max-w-lg text-xs sm:text-sm text-white/80 leading-relaxed">
                        Des collections exclusives, des résidences sélectionnées et des formations certifiées réunies au même endroit pour une expérience singulière.
                    </p>
                    <div className="mt-6 flex flex-wrap justify-center gap-3">
                        <Button variant="primary" href={route('catalog.index')} className="h-10 px-6 font-semibold bg-accent text-white hover:bg-accent-2 shadow-sm">
                            Explorer la boutique
                        </Button>
                        <Button variant="secondary" href={route('marketplace.shop')} className="h-10 px-5 border-white/20 text-white hover:bg-white/10">
                            Découvrir la place de marché
                        </Button>
                        <Button variant="ghost" href={route('affiliation')} className="h-10 px-4 text-white/90 hover:text-white hover:bg-white/10">
                            Rejoindre l'affiliation →
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}

Home.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
