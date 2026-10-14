import { useRef } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import ProductCard from '../Components/commerce/ProductCard';
import OfferCard from '../Components/commerce/OfferCard';

const UNIVERS = [
    { label: 'Savoirs & Formations', text: 'Ebooks, formations, audios', image: '/images/categories/digital.jpg', href: '/boutique?type=digital' },
    { label: "Mode & Artisanat d'art", text: 'Pièces livrées chez vous', image: '/images/categories/physical.jpg', href: '/boutique?type=physical' },
    { label: 'Résidences & Guest houses', text: 'Séjours et retraites', image: '/images/categories/residence.jpg', href: '/place-de-marche?pole=residences-guest-houses-immobilier' },
    { label: 'Soins & Praticiens', text: 'Séances sur rendez-vous', image: '/images/categories/service.jpg', href: '/boutique?type=service' },
];

const ASSURANCES = [
    { title: 'Paiement sécurisé', text: 'Mobile Money ou carte bancaire', d: ['M3 6h18v12H3z', 'M3 10h18', 'M7 15h4'] },
    { title: 'Accès immédiat', text: 'Contenus digitaux dès le paiement', d: ['M12 4v11', 'M7 10l5 5 5-5', 'M5 20h14'] },
    { title: 'Livraison suivie', text: 'Numéro de suivi par e-mail', d: ['M3 7l9-4 9 4-9 4-9-4z', 'M3 7v10l9 4 9-4V7', 'M12 11v10'] },
    { title: 'Créateurs vérifiés', text: 'Chaque offre est relue avant publication', d: ['M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z', 'M9 12l2 2 4-4'] },
];

function Icon({ d, className = 'h-5 w-5' }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"
             strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {[].concat(d).map((p) => <path key={p} d={p} />)}
        </svg>
    );
}

function ItemCard({ item }) {
    return item.kind === 'offer' ? <OfferCard offer={item} /> : <ProductCard product={item} />;
}

/** Rangée de produits défilante, avec boutons précédent/suivant sur ordinateur. */
function Rail({ id, title, subtitle, href, items, tone = 'plain' }) {
    const track = useRef(null);
    if (!items?.length) return null;

    const scroll = (dir) => {
        const el = track.current;
        if (el) el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
    };

    return (
        <section aria-labelledby={id} className={tone === 'band' ? 'bg-ground-alt py-12 sm:py-14' : 'py-12 sm:py-14'}>
            <div className="mx-auto max-w-[1280px] px-4 sm:px-6">
                <div className="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <h2 id={id} className="text-2xl sm:text-[28px]">{title}</h2>
                        {subtitle && <p className="mt-1 text-[15px] text-ink-2">{subtitle}</p>}
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {href && (
                            <Link href={href} className="mr-2 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-accent-2 hover:text-ink">
                                Tout voir
                                <Icon d="M5 12h14M13 6l6 6-6 6" className="h-4 w-4" />
                            </Link>
                        )}
                        <button type="button" onClick={() => scroll(-1)} aria-label={`${title} : produits précédents`}
                                className="hidden h-11 w-11 items-center justify-center rounded-full border border-line-strong bg-surface text-ink transition-colors hover:border-ink sm:inline-flex">
                            <Icon d="M15 6l-6 6 6 6" className="h-4 w-4" />
                        </button>
                        <button type="button" onClick={() => scroll(1)} aria-label={`${title} : produits suivants`}
                                className="hidden h-11 w-11 items-center justify-center rounded-full border border-line-strong bg-surface text-ink transition-colors hover:border-ink sm:inline-flex">
                            <Icon d="M9 6l6 6-6 6" className="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <ul ref={track}
                    className="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto px-4 pb-2 sm:scroll-px-6 [scrollbar-width:none] sm:-mx-6 sm:px-6 [&::-webkit-scrollbar]:hidden">
                    {items.map((item) => (
                        <li key={`${item.kind}-${item.id}`} className="w-[72%] shrink-0 snap-start sm:w-[calc((100%-2rem)/3)] lg:w-[calc((100%-3rem)/4)]">
                            <ItemCard item={item} />
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

function maxDiscount(items) {
    return items.reduce((max, i) => {
        const promo = i.kind === 'offer' ? i.sale_price : i.promotional_price;
        if (promo === null || promo === undefined || Number(promo) >= Number(i.price)) return max;
        return Math.max(max, Math.round(100 - (Number(promo) / Number(i.price)) * 100));
    }, 0);
}

function initials(name) {
    return name.split(/\s+/).filter((w) => /^\p{L}/u.test(w)).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
}

export default function Home({ newArrivals = [], bestSellers = [], promotions = [], vendors = [] }) {
    const { marketplaceEnabled, commissionRates } = usePage().props;
    const rates = { 1: 15, 2: 10, 3: 5, ...(commissionRates ?? {}) };
    const discount = maxDiscount(promotions);
    const marketplaceHref = marketplaceEnabled ? route('marketplace.shop') : route('marketplace');
    const nothingYet = !newArrivals.length && !bestSellers.length && !promotions.length;

    return (
        <>
            <Head title="Formations, créations, séjours et soins">
                <meta name="description" content="Formations, créations artisanales, séjours et soins : la boutique MALKUTHAR et ses créateurs. Paiement Mobile Money ou carte." />
            </Head>

            {/* 1. Vitrine d'ouverture : grande bannière + deux tuiles. */}
            <section className="mx-auto max-w-[1280px] px-4 pt-6 sm:px-6 sm:pt-8">
                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="relative overflow-hidden rounded-3xl bg-ink text-white lg:col-span-2">
                        <img src="/images/hero-craft.jpg" alt="" className="absolute inset-0 h-full w-full object-cover opacity-45 sm:left-1/3 sm:w-2/3 sm:opacity-100" />
                        <div className="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/10 sm:via-ink/80" aria-hidden="true" />
                        <div className="relative flex min-h-[340px] flex-col justify-end gap-5 p-7 sm:min-h-[420px] sm:max-w-[60%] sm:p-10">
                            <p className="inline-flex w-fit rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-accent-light">Nouveautés de la semaine</p>
                            <h1 className="text-[34px] leading-[1.08] text-white sm:text-[44px]">
                                Formations, créations et soins, au même endroit.
                            </h1>
                            <p className="max-w-md text-[15px] leading-relaxed text-white/80">
                                La boutique MALKUTHAR et ses créateurs partenaires. Paiement Mobile Money ou carte, accès immédiat aux contenus digitaux.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <Link href={route('catalog.index')} className="inline-flex min-h-12 items-center rounded-full bg-accent-light px-6 text-sm font-semibold text-ink transition-colors hover:bg-white">
                                    Voir la boutique
                                </Link>
                                <Link href={marketplaceHref} className="inline-flex min-h-12 items-center rounded-full border border-white/40 px-6 text-sm font-semibold text-white transition-colors hover:border-white">
                                    Place de marché
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-1">
                        {discount > 0 ? (
                            <a href="#promotions" className="group relative flex min-h-[170px] flex-col justify-between gap-3 overflow-hidden rounded-3xl bg-accent p-5 text-white sm:min-h-[200px] sm:p-7">
                                <p className="text-sm font-semibold text-white/85">Promotions en cours</p>
                                <div>
                                    <p className="text-[32px] font-semibold leading-none sm:text-[44px]">−{discount} %</p>
                                    <p className="mt-2 text-sm text-white/85">sur une sélection de {promotions.length} article{promotions.length > 1 ? 's' : ''}</p>
                                </div>
                                <span className="inline-flex items-center gap-1.5 text-sm font-semibold">
                                    J'en profite <Icon d="M5 12h14M13 6l6 6-6 6" className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </a>
                        ) : (
                            <Link href={route('affiliation')} className="group relative flex min-h-[170px] flex-col justify-between gap-3 overflow-hidden rounded-3xl bg-accent p-5 text-white sm:min-h-[200px] sm:p-7">
                                <p className="text-sm font-semibold text-white/85">Programme d'affiliation</p>
                                <div>
                                    <p className="text-[32px] font-semibold leading-none sm:text-[44px]">{rates[1]} %</p>
                                    <p className="mt-2 text-sm text-white/85">de commission sur vos ventes, puis {rates[2]} % et {rates[3]} %</p>
                                </div>
                                <span className="inline-flex items-center gap-1.5 text-sm font-semibold">
                                    Découvrir <Icon d="M5 12h14M13 6l6 6-6 6" className="h-4 w-4" />
                                </span>
                            </Link>
                        )}

                        <a href={route('marketplace.become-vendor')} className="group flex min-h-[170px] flex-col justify-between gap-3 rounded-3xl bg-space-vendor p-5 text-white sm:min-h-[200px] sm:p-7">
                            <p className="text-sm font-semibold text-white/85">Créateurs et praticiens</p>
                            <p className="text-lg font-semibold leading-snug sm:text-2xl">Ouvrez votre boutique sur MALKUTHAR</p>
                            <span className="inline-flex items-center gap-1.5 text-sm font-semibold">
                                Devenir vendeur <Icon d="M5 12h14M13 6l6 6-6 6" className="h-4 w-4" />
                            </span>
                        </a>
                    </div>
                </div>
            </section>

            {/* 2. Engagements, en bande compacte. */}
            <section aria-label="Nos engagements" className="mx-auto max-w-[1280px] px-4 pt-4 sm:px-6">
                <ul className="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-line bg-line lg:grid-cols-4">
                    {ASSURANCES.map((a) => (
                        <li key={a.title} className="flex items-center gap-3 bg-surface px-3 py-3 sm:px-5 sm:py-4">
                            <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent-2">
                                <Icon d={a.d} />
                            </span>
                            <span className="min-w-0">
                                <span className="block text-sm font-semibold text-ink">{a.title}</span>
                                <span className="hidden text-[13px] text-ink-2 sm:block">{a.text}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            </section>

            {/* 3. Univers. */}
            <section aria-labelledby="univers" className="mx-auto max-w-[1280px] px-4 pt-12 sm:px-6 sm:pt-14">
                <h2 id="univers" className="mb-6 text-2xl sm:text-[28px]">Acheter par univers</h2>
                <ul className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    {UNIVERS.map((u) => (
                        <li key={u.label}>
                            <Link href={u.href} className="group relative block aspect-[4/5] overflow-hidden rounded-2xl bg-ink sm:aspect-[4/3]">
                                <img src={u.image} alt="" className="absolute inset-0 h-full w-full object-cover opacity-80 transition-opacity group-hover:opacity-100" />
                                <span className="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/20 to-transparent" aria-hidden="true" />
                                <span className="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                    <span className="block text-base font-semibold text-white sm:text-lg">{u.label}</span>
                                    <span className="mt-0.5 block text-xs text-white/80 sm:text-sm">{u.text}</span>
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            </section>

            {/* 4. Rayons. */}
            <Rail id="nouveautes" title="Nouveautés" subtitle="Les derniers ajouts de la boutique et des créateurs." href={route('catalog.index')} items={newArrivals} />
            <div id="promotions">
                <Rail id="titre-promotions" title="Promotions" subtitle={discount > 0 ? `Jusqu'à −${discount} % sur une sélection.` : null} href={`${route('catalog.index')}?dispo=promo`} items={promotions} tone="band" />
            </div>
            <Rail id="meilleures-ventes" title="Meilleures ventes" subtitle="Les produits les plus commandés." href={route('catalog.index')} items={bestSellers} />

            {nothingYet && (
                <section className="mx-auto max-w-[1280px] px-4 py-12 sm:px-6">
                    <div className="rounded-2xl border border-line bg-surface p-8 text-center">
                        <h2 className="text-2xl">La boutique se remplit</h2>
                        <p className="mx-auto mt-2 max-w-md text-[15px] text-ink-2">Les premiers produits arrivent. En attendant, parcourez les univers ou la place de marché.</p>
                        <Link href={route('catalog.index')} className="mt-5 inline-flex min-h-11 items-center rounded-full bg-ink px-6 text-sm font-semibold text-white">Voir la boutique</Link>
                    </div>
                </section>
            )}

            {/* 5. Créateurs. */}
            {vendors.length > 0 && (
                <section aria-labelledby="createurs" className="mx-auto max-w-[1280px] px-4 py-12 sm:px-6 sm:py-14">
                    <div className="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <h2 id="createurs" className="text-2xl sm:text-[28px]">Créateurs à découvrir</h2>
                            <p className="mt-1 text-[15px] text-ink-2">Artisans, formateurs, hôtes et praticiens vérifiés.</p>
                        </div>
                        <Link href={marketplaceHref} className="inline-flex min-h-11 shrink-0 items-center gap-1.5 text-sm font-semibold text-accent-2 hover:text-ink">
                            Tous les créateurs <Icon d="M5 12h14M13 6l6 6-6 6" className="h-4 w-4" />
                        </Link>
                    </div>
                    <ul className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                        {vendors.map((v) => (
                            <li key={v.slug}>
                                <Link href={route('marketplace.shop.vendor', v.slug)}
                                      className="flex h-full items-center gap-4 rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong">
                                    {v.logo_url ? (
                                        <img src={v.logo_url} alt="" className="h-14 w-14 shrink-0 rounded-full border border-line object-cover" />
                                    ) : (
                                        <span className="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-base font-semibold text-space-vendor" aria-hidden="true">
                                            {initials(v.display_name)}
                                        </span>
                                    )}
                                    <span className="min-w-0">
                                        <span className="block truncate text-[15px] font-semibold text-ink">{v.display_name}</span>
                                        <span className="block truncate text-[13px] text-ink-2">{[v.city, v.region].filter(Boolean).join(', ') || 'Bénin'}</span>
                                        <span className="mt-1 block text-xs font-semibold text-space-vendor">{v.offers_count} offre{v.offers_count > 1 ? 's' : ''}</span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {/* 6. Affiliation, en bandeau. */}
            <section className="mx-auto max-w-[1280px] px-4 pb-16 sm:px-6">
                <div className="flex flex-col gap-6 rounded-3xl bg-ink p-7 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between">
                    <div className="max-w-xl">
                        <h2 className="text-2xl text-white sm:text-[28px]">Recommandez et touchez une commission</h2>
                        <p className="mt-2 text-[15px] text-white/75">Partagez votre lien sur les produits digitaux éligibles. Chaque vente rémunère trois niveaux.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-6">
                        <dl className="flex gap-6">
                            {[[rates[1], 'vos ventes'], [rates[2], 'niveau 2'], [rates[3], 'niveau 3']].map(([r, l]) => (
                                <div key={l}>
                                    <dt className="sr-only">{l}</dt>
                                    <dd className="text-[28px] font-semibold leading-none text-accent-light">{r} %</dd>
                                    <dd className="mt-1 text-xs text-white/70">{l}</dd>
                                </div>
                            ))}
                        </dl>
                        <Link href={route('affiliation')} className="inline-flex min-h-12 items-center rounded-full bg-accent-light px-6 text-sm font-semibold text-ink transition-colors hover:bg-white">
                            Devenir affilié
                        </Link>
                    </div>
                </div>
            </section>
        </>
    );
}

Home.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
