import { useEffect, useMemo, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import SectionHeading from '../Components/ui/SectionHeading';
import Reassurance from '../Components/commerce/Reassurance';

const TESTIMONIALS = [
    { quote: "J'ai reçu la formation quelques minutes après le paiement. Tout était clair, du panier au téléchargement.", name: 'Aïcha D.', role: 'Cliente · Cotonou' },
    { quote: "J'ai partagé mon lien dans mon cercle et touché mes premiers retraits en Mobile Money le mois suivant.", name: 'Komla A.', role: 'Affilié · Lomé' },
    { quote: "J'ai réservé une séance en visio : le créneau proposé était réel, la confirmation immédiate.", name: 'Sarah M.', role: 'Cliente · Abidjan' },
    { quote: "Le coffret est arrivé suivi jusqu'à ma porte. Enfin une maison qui réunit les livres, les cours et les séances.", name: 'Ibrahim T.', role: 'Client · Ouagadougou' },
    { quote: 'Interface claire, paiement local sans friction. J\'ai recommandé la plateforme à tout mon groupe de lecture.', name: 'Ngozi E.', role: 'Cliente · Douala' },
    { quote: 'En tant qu\'auteur, je publie mes ebooks et je suis payé dès la vente. La gestion est transparente.', name: 'Yao K.', role: 'Vendeur · Kara' },
];

const AFFILIATION_STEPS = [
    ['Activez votre espace', 'En un clic depuis votre compte. Aucun second compte à créer.'],
    ['Partagez votre lien', 'Votre lien de parrainage personnel, sur les réseaux ou en message.'],
    ['La vente est attribuée', 'Un achat via votre lien sur un produit digital éligible vous est rattaché.'],
    ['La commission tombe', 'Créditée dès le paiement, retirable après la période de validation.'],
];

function CategoryTabs({ categories }) {
    const [tab, setTab] = useState(categories[0]?.type);
    const active = categories.find((c) => c.type === tab) ?? categories[0];

    return (
        <div className="mt-6">
            <div className="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                <div className="flex min-w-max items-center justify-center gap-3 py-1 sm:min-w-0">
                    {categories.map((c) => (
                        <button key={c.type} type="button" onClick={() => setTab(c.type)} aria-pressed={tab === c.type}
                                className={['inline-flex flex-shrink-0 items-center gap-2.5 rounded-xl border px-4 py-2.5 text-[15px] font-medium transition-all',
                                    tab === c.type ? 'border-line-strong bg-surface text-ink shadow-sm' : 'border-transparent bg-ground-alt text-ink-2 hover:border-line hover:bg-surface'].join(' ')}>
                            <span className="inline-flex h-5 w-5 items-center justify-center rounded-md text-white" style={{ backgroundColor: c.color }}>
                                <svg className="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round"
                                     dangerouslySetInnerHTML={{ __html: c.icon }} />
                            </span>
                            {c.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="mt-5 rounded-2xl border border-line bg-surface p-5 sm:p-6">
                {active && (
                    <div className="grid gap-6 sm:grid-cols-2 sm:items-center">
                        <div className="order-2 sm:order-1">
                            <div className="flex items-center gap-2.5">
                                <span className="inline-flex h-6 w-6 items-center justify-center rounded-md text-white" style={{ backgroundColor: active.color }}>
                                    <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"
                                         dangerouslySetInnerHTML={{ __html: active.icon }} />
                                </span>
                                <h3 className="font-display text-xl font-semibold text-ink">{active.label}</h3>
                            </div>
                            <p className="mt-3 text-ink-2">{active.text}</p>
                            <a href={route('catalog.index', { type: active.type })}
                               className="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent hover:underline">
                                Voir les {active.label.toLowerCase()} <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div className="relative order-1 aspect-[4/3] overflow-hidden rounded-xl border border-line bg-ground-alt sm:order-2">
                            <div className="absolute inset-0 flex items-center justify-center text-line-strong">
                                <svg className="h-16 w-16" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1" strokeLinecap="round" strokeLinejoin="round"
                                     dangerouslySetInnerHTML={{ __html: active.icon }} />
                            </div>
                            <img src={`/${active.image}`} alt={`Aperçu — ${active.label}`} loading="lazy"
                                 onError={(e) => e.currentTarget.remove()}
                                 className="relative h-full w-full object-cover" />
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
        <div className="mt-6" onMouseEnter={() => {}} onMouseLeave={() => {}}>
            <div className="overflow-hidden">
                <div className="flex transition-transform duration-500 ease-out" style={{ transform: `translateX(-${page * 100}%)` }}>
                    {slices.map((items, p) => (
                        <div key={p} className="w-full shrink-0">
                            <div className="grid gap-4 pb-1 sm:grid-cols-2 lg:grid-cols-3">
                                {items.map((t, idx) => (
                                    <figure key={idx} className="flex h-full flex-col rounded-2xl border border-line bg-surface p-5 sm:p-6">
                                        <svg className="h-6 w-6 text-accent/40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M10 7H6a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v3H5v2h5v-9a2 2 0 0 0-2-2H6V9h4V7zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v3h-3v2h5v-9a2 2 0 0 0-2-2h-2V9h4V7z" />
                                        </svg>
                                        <blockquote className="quote mt-3 flex-1 text-[17px] leading-relaxed text-ink">« {t.quote} »</blockquote>
                                        <figcaption className="mt-5 text-sm">
                                            <span className="font-semibold text-ink">{t.name}</span>
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
                        <button key={p} type="button" onClick={() => setPage(p)} aria-label={`Page ${p + 1}`}
                                className={['h-1.5 rounded-full transition-all', page === p ? 'w-5 bg-accent' : 'w-1.5 bg-line-strong hover:bg-ink-3'].join(' ')} />
                    ))}
                </div>
                <div className="flex gap-1">
                    <button type="button" onClick={() => setPage((p) => (p - 1 + pages) % pages)} aria-label="Précédent"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M10 3L5 8l5 5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    </button>
                    <button type="button" onClick={() => setPage((p) => (p + 1) % pages)} aria-label="Suivant"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M6 3l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Home() {
    const { categories = [], marketplaceEnabled } = usePage().props;

    return (
        <>
            <Head title="MALKUTHAR — Produits digitaux, physiques et services">
                <meta name="description" content="MALKUTHAR réunit trois métiers : ebooks et formations en livraison immédiate, produits physiques expédiés, consultations sur rendez-vous. Programme d'affiliation à 3 niveaux." />
            </Head>

            <section className="relative overflow-hidden">
                <div className="relative mx-auto max-w-[1180px] px-4 pb-20 pt-20 text-center sm:px-6 sm:pt-28">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-accent">MALKUTHAR SARL</p>
                    <h1 className="mx-auto mt-5 max-w-3xl font-display text-[clamp(2.1rem,6vw,4rem)] font-semibold leading-[1.12] tracking-[-0.018em] text-ink">
                        Transmettez vos savoirs, vos œuvres, <span className="quote font-normal text-accent">votre présence.</span>
                    </h1>
                    <p className="mx-auto mt-6 max-w-xl text-lg text-ink-2">
                        Une plateforme, trois voies : vos écrits et formations livrés à l'instant, vos objets expédiés, vos
                        accompagnements réservés. Bientôt, une place de marché ouverte aux praticiens de la région — et un
                        programme d'affiliation à trois niveaux.
                    </p>
                    <div className="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Button variant="primary" href={route('catalog.index')}>Explorer la boutique</Button>
                        <Button variant="ghost" href={route('register')} native>Devenir affilié →</Button>
                    </div>
                    <p className="mt-7 text-[13px] text-ink-3">Livraison numérique immédiate · Paiement FedaPay &amp; CinetPay</p>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <SectionHeading eyebrow="Trois voies, une maison" title="Par où voulez-vous entrer ?" />
                <CategoryTabs categories={categories} />
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <SectionHeading eyebrow="Ils nous font confiance" title="Ce qu'en disent nos membres" />
                <TestimonialsSlider />
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <div className="relative overflow-hidden rounded-3xl border border-black/10 bg-surface p-6 sm:p-10">
                    <div className="relative max-w-2xl">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Programme d'affiliation</p>
                        <h2 className="mt-3 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Gagnez en partageant</h2>
                        <p className="mt-3 text-ink-2">
                            Réservé aux produits digitaux éligibles.{' '}
                            <span className="font-semibold text-ink">15 % · 10 % · 5 %</span> répartis sur trois niveaux de parrainage.
                        </p>
                    </div>
                    <ol className="relative mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {AFFILIATION_STEPS.map(([titre, texte], i) => (
                            <li key={titre} className="flex gap-3">
                                <span className="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-[13px] font-semibold text-accent">
                                    {i + 1}
                                </span>
                                <div>
                                    <p className="font-display text-[14px] font-semibold text-ink">{titre}</p>
                                    <p className="mt-1 text-[13px] leading-snug text-ink-2">{texte}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Button variant="primary" href={route('register')} native>Rejoindre le programme</Button>
                        <Button variant="ghost" href={route('affiliation')}>Comment ça marche →</Button>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <div className="rounded-3xl border border-line bg-ground-alt p-6 sm:p-10">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
                        {!marketplaceEnabled && (
                            <span className="rounded-full border border-accent/30 bg-accent-soft/60 px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-[0.1em] text-accent">Bientôt</span>
                        )}
                    </div>
                    <h2 className="mt-3 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Une place de marché ouverte aux praticiens</h2>
                    <p className="mt-3 max-w-xl text-ink-2">
                        {marketplaceEnabled
                            ? 'Praticiens, créateurs, formateurs et artisans de la région vendent déjà leurs offres sur MALKUTHAR, sous leur propre nom : produits physiques, produits digitaux, services et formations, chaque fiche validée par notre équipe avant sa mise en ligne.'
                            : 'Praticiens, créateurs, formateurs et artisans de la région pourront bientôt vendre leurs offres sur MALKUTHAR, sous leur propre nom : produits physiques, produits digitaux, services et formations, avec validation de chaque fiche par notre équipe.'}
                    </p>
                    <div className="mt-6 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        {['Produits physiques', 'Produits digitaux', 'Services', 'Formations'].map((t) => (
                            <div key={t} className="rounded-xl border border-line bg-surface px-3 py-2.5 text-[13px] font-medium text-ink">{t}</div>
                        ))}
                    </div>
                    <div className="mt-7 flex flex-wrap gap-3">
                        {marketplaceEnabled ? (
                            <>
                                <Button variant="primary" href={route('marketplace.shop')}>Découvrir la place de marché →</Button>
                                <Button variant="secondary" href={route('marketplace')}>Devenir vendeur</Button>
                            </>
                        ) : (
                            <Button variant="secondary" href={route('marketplace')}>Vendre sur MALKUTHAR →</Button>
                        )}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
                <div className="rounded-2xl border border-black/10 bg-surface p-6 sm:p-8">
                    <Reassurance />
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 pb-24 text-center sm:px-6">
                <h2 className="font-display text-xl font-semibold text-ink">Prêt à ouvrir la boutique ?</h2>
                <div className="mt-5">
                    <Button variant="primary" href={route('catalog.index')}>Voir tous les produits</Button>
                </div>
            </section>
        </>
    );
}

Home.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
