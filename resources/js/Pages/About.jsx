import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import SectionHeading from '../Components/ui/SectionHeading';

const METIERS = [
    {
        title: 'Produits Digitaux',
        badge: 'Savoirs & Académie',
        image: '/images/categories/digital.jpg',
        text: 'Ebooks, formations, audio et programmes numériques d\'éveil. Livrés immédiatement après paiement et protégés par liens sécurisés.',
        href: '/boutique?type=digital',
    },
    {
        title: 'Objets & Créations Physiques',
        badge: 'Artisanat & Mode',
        image: '/images/categories/physical.jpg',
        text: 'Ouvrages d\'exception, maroquinerie, accessoires sacrés et parures. Gestion rigoureuse des stocks, expédition soignée et suivi complet.',
        href: '/boutique?type=physical',
    },
    {
        title: 'Services & Consultations',
        badge: 'Soins & Praticiens',
        image: '/images/categories/service.jpg',
        text: 'Consultations d\'orientation, bilans personnalisés et soins dispensés par nos praticiens partenaires, en ligne ou en présentiel.',
        href: '/boutique?type=service',
    },
];

const VALEURS = [
    {
        title: 'Immédiateté',
        text: 'Pour le digital, aucun délai : le lien de téléchargement sécurisé part dès la confirmation du règlement.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
        ),
    },
    {
        title: 'Excellence & Fiabilité',
        text: 'Chaque création est validée manuellement. Chaque paiement est vérifié côté serveur auprès de nos prestataires certifiés.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        ),
    },
    {
        title: 'Transparence Absolue',
        text: 'Pour nos partenaires et créateurs, tout mouvement financier est tracé dans un registre immuable : commissions, délais et retraits.',
        icon: (
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        ),
    },
];

export default function About() {
    return (
        <>
            <Head title="À propos de MALKUTHAR">
                <meta
                    name="description"
                    content="Découvrez MALKUTHAR : maison d'excellence réunissant sous un même toit produits digitaux, créations physiques, résidences et services certifiés."
                />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 py-6 sm:px-6 sm:py-8 space-y-12">
                {/* 1. HERO BANNER — STYLE RÉFÉRENCE AVEC PHOTO D'ATELIER */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                        <div className="flex flex-col justify-center">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                À propos de la maison
                            </span>
                            <h1 className="mt-3 sm:mt-4 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl lg:text-4xl leading-[1.15]">
                                Une maison d'exception,
                                <br />
                                trois grands métiers
                            </h1>
                            <p className="mt-3 max-w-xl text-xs sm:text-sm leading-relaxed text-ink-2">
                                MALKUTHAR est une maison indépendante pensée pour concevoir, certifier et diffuser des œuvres
                                et prestations authentiques : savoirs digitaux, artisanat physique noble, résidences et accompagnements personnalisés.
                            </p>
                            <div className="mt-6 flex flex-wrap items-center gap-3">
                                <Button variant="primary" href={route('catalog.index')} className="h-10 px-5 text-xs sm:text-sm font-semibold shadow-xs">
                                    Explorer la boutique
                                </Button>
                                <Button variant="secondary" href="#metiers" native className="h-10 px-4 text-xs sm:text-sm font-medium">
                                    Découvrir nos 3 pôles ↓
                                </Button>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src="/images/about-maison.jpg"
                                alt="Atelier et univers de la maison MALKUTHAR"
                                className="h-52 sm:h-64 md:h-72 lg:h-80 w-full object-cover object-center"
                            />
                        </div>
                    </div>
                </section>

                {/* 2. NOS TROIS MÉTIERS — CARTES RICHES EN IMAGES */}
                <section id="metiers" className="space-y-6">
                    <div className="text-center max-w-xl mx-auto">
                        <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                            L'Écosystème MALKUTHAR
                        </span>
                        <h2 className="mt-2 font-display text-2xl sm:text-3xl font-bold text-ink">
                            Nos trois piliers de création
                        </h2>
                    </div>

                    <div className="grid gap-6 md:grid-cols-3">
                        {METIERS.map((m) => (
                            <div key={m.title} className="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-2xs transition-all hover:shadow-xs">
                                <div className="relative h-44 w-full overflow-hidden bg-ground">
                                    <img
                                        src={m.image}
                                        alt={m.title}
                                        className="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                                    />
                                    <span className="absolute top-3 left-3 rounded-lg bg-surface/90 backdrop-blur-sm px-2.5 py-1 text-[11px] font-bold text-ink border border-line/80 shadow-2xs">
                                        {m.badge}
                                    </span>
                                </div>
                                <div className="flex flex-1 flex-col p-5">
                                    <h3 className="font-display text-base font-bold text-ink">
                                        {m.title}
                                    </h3>
                                    <p className="mt-2 text-xs leading-relaxed text-ink-2 flex-1">
                                        {m.text}
                                    </p>
                                    <div className="mt-4 pt-3 border-t border-line/60">
                                        <Link href={m.href} className="text-xs font-bold text-accent hover:text-accent-2 inline-flex items-center gap-1">
                                            <span>Découvrir la collection</span>
                                            <span>→</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 3. NOS VALEURS & APPROCHE */}
                <section className="rounded-3xl border border-line bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <SectionHeading eyebrow="Notre approche" title="Ce à quoi nous tenons" />
                    <div className="mt-8 grid gap-6 sm:grid-cols-3">
                        {VALEURS.map((v) => (
                            <div key={v.title} className="rounded-2xl border border-line bg-surface p-5 shadow-2xs">
                                <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent/15 text-accent">
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                        {v.icon}
                                    </svg>
                                </span>
                                <h3 className="mt-3 font-display text-base font-bold text-ink">{v.title}</h3>
                                <p className="mt-1.5 text-xs leading-relaxed text-ink-2">{v.text}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 4. PROGRAMME D'AFFILIATION & PARTENARIATS */}
                <section className="rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-surface p-6 sm:p-8 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-6">
                    <div className="max-w-2xl">
                        <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                            Programme Partenaires
                        </span>
                        <h2 className="mt-2 font-display text-xl sm:text-2xl font-bold text-ink">
                            Partagez nos créations, gagnez sur 3 niveaux
                        </h2>
                        <p className="mt-2 text-xs sm:text-sm text-ink-2 leading-relaxed">
                            Bénéficiez d'une rémunération transparente jusqu'à <strong className="text-ink font-semibold">30 %</strong> (15 % · 10 % · 5 %)
                            sur les produits digitaux éligibles avec un suivi en temps réel de votre lignée.
                        </p>
                    </div>
                    <div className="flex shrink-0 flex-wrap items-center gap-3">
                        <Button variant="primary" href={route('affiliation')} className="h-10 px-5 text-xs font-semibold">
                            Découvrir le programme
                        </Button>
                        <Button variant="ghost" href={route('affiliate.program-terms')} native className="h-10 px-4 text-xs font-medium">
                            Lire les conditions →
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}

About.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
