import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';

const METIERS = [
    {
        title: 'Produits digitaux',
        image: '/images/categories/digital.jpg',
        text: "Ebooks, formations et programmes audio. Le lien de téléchargement est envoyé dès que le paiement est confirmé.",
        href: '/boutique?type=digital',
    },
    {
        title: 'Objets et créations',
        image: '/images/categories/physical.jpg',
        text: "Livres d'art, maroquinerie et accessoires. Stock tenu à jour, expédition avec numéro de suivi.",
        href: '/boutique?type=physical',
    },
    {
        title: 'Services et consultations',
        image: '/images/categories/service.jpg',
        text: 'Consultations, bilans et séances avec des praticiens partenaires, en ligne ou en présentiel.',
        href: '/boutique?type=service',
    },
];

const PRINCIPES = [
    {
        title: 'Pas d’attente pour le numérique',
        text: 'Le lien de téléchargement sécurisé part dès la confirmation du règlement.',
    },
    {
        title: 'Une vérification à chaque étape',
        text: 'Chaque produit est relu avant publication. Chaque paiement est vérifié côté serveur auprès du prestataire.',
    },
    {
        title: 'Des comptes lisibles',
        text: 'Pour les affiliés et les vendeurs, chaque commission, délai et retrait est enregistré et consultable.',
    },
];

export default function About() {
    return (
        <>
            <Head title="À propos de MALKUTHAR">
                <meta
                    name="description"
                    content="MALKUTHAR réunit produits digitaux, créations physiques, résidences et services de praticiens sur une même boutique."
                />
            </Head>

            {/* Ouverture */}
            <section className="border-b border-line">
                <div className="mx-auto grid max-w-[1280px] items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:py-20">
                    <div>
                        <p className="text-sm font-medium text-accent-2">À propos</p>
                        <h1 className="mt-3 text-[clamp(2.25rem,5vw,3.5rem)] font-semibold leading-[1.06] text-ink">
                            Une maison, trois métiers
                        </h1>
                        <p className="mt-6 max-w-xl text-base leading-relaxed text-ink-2 sm:text-lg">
                            MALKUTHAR est une maison indépendante. Nous sélectionnons et diffusons des savoirs numériques,
                            des objets d'artisanat, des résidences et des accompagnements, avec le même soin du paiement à la livraison.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button variant="primary" href={route('catalog.index')}>Explorer la boutique</Button>
                            <Button variant="secondary" href="#metiers" native>Nos métiers</Button>
                        </div>
                    </div>
                    <div className="overflow-hidden rounded-2xl bg-ground-alt">
                        <img
                            src="/images/about-maison.jpg"
                            alt="Atelier de la maison MALKUTHAR"
                            className="aspect-[4/3] h-full w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-[1280px] space-y-20 px-4 py-16 sm:px-6 sm:py-20 lg:space-y-28">
                {/* Métiers */}
                <section id="metiers" aria-labelledby="metiers-titre" className="scroll-mt-24">
                    <h2 id="metiers-titre" className="text-3xl text-ink sm:text-4xl">Nos métiers</h2>
                    <ul className="mt-8 grid gap-5 md:grid-cols-3">
                        {METIERS.map((m) => (
                            <li key={m.title}>
                                <Link href={m.href} className="group flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-colors hover:border-line-strong">
                                    <div className="aspect-[16/10] overflow-hidden bg-ground-alt">
                                        <img src={m.image} alt="" loading="lazy" className="h-full w-full object-cover" />
                                    </div>
                                    <div className="flex flex-1 flex-col p-6">
                                        <h3 className="text-[17px] text-ink group-hover:text-accent-2">{m.title}</h3>
                                        <p className="mt-2 flex-1 text-sm leading-relaxed text-ink-2">{m.text}</p>
                                        <span className="mt-5 text-sm font-semibold text-accent-2">Voir la sélection</span>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>

                {/* Principes */}
                <section aria-labelledby="principes-titre">
                    <h2 id="principes-titre" className="text-3xl text-ink sm:text-4xl">Ce à quoi nous tenons</h2>
                    <ul className="mt-10 grid gap-x-8 gap-y-10 md:grid-cols-3">
                        {PRINCIPES.map((p) => (
                            <li key={p.title} className="border-t border-line-strong pt-6">
                                <h3 className="text-base text-ink">{p.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-ink-2">{p.text}</p>
                            </li>
                        ))}
                    </ul>
                </section>

                {/* Affiliation */}
                <section className="flex flex-col gap-8 rounded-2xl bg-ink p-7 text-white sm:p-10 md:flex-row md:items-end md:justify-between">
                    <div className="max-w-2xl">
                        <h2 className="text-3xl text-white">Recommandez nos produits, sur trois niveaux</h2>
                        <p className="mt-4 text-[15px] leading-relaxed text-night-ink-2">
                            Jusqu'à 30 % au total (15 % · 10 % · 5 %) sur les produits digitaux éligibles,
                            avec un suivi en temps réel de vos ventes et de vos filleuls.
                        </p>
                    </div>
                    <div className="flex shrink-0 flex-col gap-3 sm:flex-row">
                        <Link href={route('affiliation')}
                              className="inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2">
                            Découvrir le programme
                        </Link>
                        <a href={route('affiliate.program-terms')}
                           className="inline-flex min-h-11 items-center justify-center rounded-full border border-night-line px-6 text-sm font-semibold text-night-ink transition-colors hover:border-night-ink-2">
                            Lire les conditions
                        </a>
                    </div>
                </section>
            </div>
        </>
    );
}

About.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
