import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import OfferCard from '../../Components/commerce/OfferCard';
import { money } from '../../lib/format';
import { getColorHex } from '../../lib/colors';

const TYPE_CONFIG = {
    physical: { label: 'Produits physiques', shortLabel: 'Physique' },
    digital: { label: 'Produits digitaux', shortLabel: 'Digital' },
    service: { label: 'Services & Consultations', shortLabel: 'Service' },
    training: { label: 'Formations & Ateliers', shortLabel: 'Formation' },
};

export default function Shop({
    offers,
    poles = [],
    activePole = null,
    activePoleCategory = null,
    rootCategories = [],
    category,
    facetFields = [],
    activeType,
    activeFacets = {},
    vendors = [],
    priceBounds = { min: 0, max: 150000 },
    availableColors = [],
    query = {},
}) {
    const q = query ?? {};

    // Accordions collapse state (tout ouvert par défaut comme dans la référence)
    const [sections, setSections] = useState({
        price: true,
        categories: true,
        vendors: true,
        types: true,
        colors: true,
        availability: true,
    });

    const toggleSection = (key) => {
        setSections((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    // Filtres locaux de formulaire
    const [searchTerm, setSearchTerm] = useState(q.q ?? '');
    const [priceMin, setPriceMin] = useState(q.prix_min ?? '');
    const [priceMax, setPriceMax] = useState(q.prix_max ?? '');
    const [selectedColor, setSelectedColor] = useState('');
    const [mobileFiltersOpen, setMobileFiltersOpen] = useState(false);

    const buildParams = (overrides = {}) => {
        const params = {
            q: q.q,
            pole: q.pole,
            categorie: category?.slug,
            type: activeType,
            prix_min: q.prix_min,
            prix_max: q.prix_max,
            vendeur: q.vendeur,
            dispo: q.dispo,
            couleur: q.couleur,
            region: q.region,
            f: activeFacets,
            tri: q.tri,
            ...overrides,
        };

        // Nettoyage des valeurs vides
        Object.keys(params).forEach((k) => {
            if (params[k] === undefined || params[k] === null || params[k] === '') {
                delete params[k];
            }
        });

        return params;
    };

    const apply = (overrides = {}) => {
        router.get(route('marketplace.shop'), buildParams(overrides), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        apply({
            q: searchTerm.trim() || undefined,
            prix_min: priceMin !== '' ? priceMin : undefined,
            prix_max: priceMax !== '' ? priceMax : undefined,
        });
        setMobileFiltersOpen(false);
    };

    // Chips des filtres actifs
    const activeChips = [];
    if (q.q) activeChips.push({ label: `« ${q.q} »`, remove: () => { setSearchTerm(''); apply({ q: undefined }); } });
    if (q.pole && activePoleCategory) {
        activeChips.push({
            label: `Pôle : ${activePoleCategory.name}`,
            remove: () => apply({ pole: undefined, categorie: undefined }),
        });
    }
    if (category) activeChips.push({ label: `Catégorie : ${category.name}`, remove: () => apply({ categorie: undefined }) });
    if (activeType) activeChips.push({ label: TYPE_CONFIG[activeType]?.label ?? activeType, remove: () => apply({ type: undefined }) });
    if (q.prix_min || q.prix_max) {
        activeChips.push({
            label: `Prix : ${q.prix_min ? Number(q.prix_min).toLocaleString('fr-FR') : '0'} – ${q.prix_max ? Number(q.prix_max).toLocaleString('fr-FR') : '∞'} FCFA`,
            remove: () => { setPriceMin(''); setPriceMax(''); apply({ prix_min: undefined, prix_max: undefined }); },
        });
    }
    if (q.vendeur) {
        const v = vendors.find((vend) => vend.slug === q.vendeur || String(vend.id) === String(q.vendeur));
        activeChips.push({ label: `Créateur : ${v?.name ?? q.vendeur}`, remove: () => apply({ vendeur: undefined }) });
    }
    if (q.dispo === 'promo') {
        activeChips.push({ label: 'En promotion', remove: () => apply({ dispo: undefined }) });
    }
    if (q.couleur) {
        activeChips.push({ label: `Couleur : ${q.couleur}`, remove: () => apply({ couleur: undefined }) });
    }

    const hasActiveFilters = activeChips.length > 0;

    // Détermination de l'image de bannière selon le pôle
    const bannerImage = (() => {
        if (activePole === 'residences-guest-houses-immobilier') return '/images/categories/residence.jpg';
        if (activePole === 'mode-beaute-cosmetiques') return '/images/categories/physical.jpg';
        if (activePole === 'services-praticiens-specialises') return '/images/categories/service.jpg';
        if (activePole === 'savoirs-formations-en-ligne') return '/images/categories/digital.jpg';
        return '/images/catalog-banner.jpg';
    })();

    const bannerTitle = (() => {
        if (activePole === 'residences-guest-houses-immobilier') {
            return { line1: 'Trouvez la résidence', line2: 'qui vous correspond' };
        }
        if (activePole === 'mode-beaute-cosmetiques') {
            return { line1: 'Trouvez la pièce de mode', line2: 'qui vous correspond' };
        }
        if (activePole === 'services-praticiens-specialises') {
            return { line1: 'Trouvez le soin', line2: 'qui vous correspond' };
        }
        if (activePole === 'savoirs-formations-en-ligne') {
            return { line1: 'Trouvez la formation', line2: 'qui vous correspond' };
        }
        return { line1: 'Trouvez la création', line2: 'qui vous correspond' };
    })();

    return (
        <>
            <Head title={`Place de marché${activePoleCategory ? ` — ${activePoleCategory.name}` : ''}${category ? ` — ${category.name}` : ''}`}>
                <meta name="description" content="Catalogue d'excellence des créateurs, résidences, artisans et praticiens partenaires de la maison MALKUTHAR." />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 py-4 sm:px-6 sm:py-6">
                {/* BANNIÈRE EXACTE SELON LA RÉFÉRENCE UTILISATEUR */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-5 sm:p-7 md:p-8 lg:p-9 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-6 md:grid-cols-2 lg:gap-10">
                        {/* Côté gauche : Surtitre orange souligné + Titre percutant 2 lignes */}
                        <div className="flex flex-col justify-center">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                {activePoleCategory ? activePoleCategory.name : 'Nos produits'}
                            </span>
                            <h1 className="mt-3 sm:mt-4 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl lg:text-4xl leading-[1.15]">
                                {bannerTitle.line1}
                                <br />
                                {bannerTitle.line2}
                            </h1>
                        </div>

                        {/* Côté droit : Photo encadrée avec ses propres coins arrondis, sans voile blanc */}
                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src={bannerImage}
                                alt={activePoleCategory?.name ?? 'Sélection Marketplace MALKUTHAR'}
                                className="h-44 sm:h-52 md:h-56 lg:h-60 w-full object-cover object-center"
                            />
                        </div>
                    </div>
                </section>

                {/* Barre de navigation par Univers */}
                <div className="mt-4 mb-6 flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <span className="hidden text-[11px] font-bold uppercase tracking-wider text-ink-3 lg:inline-block pr-1 shrink-0">
                        Univers :
                    </span>

                    {poles.map((p) => {
                        const isAll = !p.slug || p.key === 'all';
                        const isActive = isAll ? !activePole : activePole === p.slug;
                        const label = p.label || p.name || 'Univers';

                        return (
                            <button
                                key={p.slug ?? 'all'}
                                type="button"
                                onClick={() => apply({ pole: isAll ? undefined : (isActive ? undefined : p.slug), categorie: undefined })}
                                className={`inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs font-semibold transition-all duration-150 ${
                                    isActive
                                        ? 'bg-ink text-white shadow-xs'
                                        : 'border border-line/80 bg-surface text-ink-2 hover:border-accent/60 hover:bg-ground hover:text-ink'
                                }`}
                            >
                                <span className="flex h-3.5 w-3.5 shrink-0 items-center justify-center">
                                    {(p.slug === 'mode-beaute-cosmetiques' || p.key === 'fashion_beauty') ? (
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    ) : (p.slug === 'residences-guest-houses-immobilier' || p.key === 'real_estate_hospitality') ? (
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    ) : (p.slug === 'services-praticiens-specialises' || p.key === 'services_wellness') ? (
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    ) : (p.slug === 'savoirs-formations-en-ligne' || p.key === 'digital_academy') ? (
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
                                        </svg>
                                    ) : (
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    )}
                                </span>
                                <span>{label}</span>
                            </button>
                        );
                    })}
                </div>

                {/* Mobile Filter Toggle Button */}
                <div className="mb-4 flex items-center justify-between lg:hidden">
                    <button
                        type="button"
                        onClick={() => setMobileFiltersOpen(!mobileFiltersOpen)}
                        className="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink shadow-xs"
                    >
                        <svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M4 6h16M7 12h10m-8 6h6" strokeLinecap="round" />
                        </svg>
                        <span>Filtrer par caractéristiques</span>
                    </button>
                    <span className="text-xs text-ink-3">
                        {offers.total} article{offers.total > 1 ? 's' : ''}
                    </span>
                </div>

                {/* 2. MAIN 2-COLUMN LAYOUT: Sidebar (Left) + Grid (Right) */}
                <div className="grid grid-cols-1 items-start gap-8 lg:grid-cols-[270px_1fr]">
                    {/* LEFT SIDEBAR: FILTRER PAR CARACTÉRISTIQUES */}
                    <aside
                        className={`fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto bg-surface p-6 shadow-2xl transition-transform duration-300 lg:static lg:z-auto lg:w-full lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none ${
                            mobileFiltersOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                        }`}
                    >
                        {/* Mobile close bar */}
                        <div className="mb-4 flex items-center justify-between border-b border-line pb-3 lg:hidden">
                            <span className="font-display text-sm font-bold text-ink">Filtres</span>
                            <button
                                type="button"
                                onClick={() => setMobileFiltersOpen(false)}
                                className="rounded-lg p-1.5 text-ink-3 hover:bg-ground-alt hover:text-ink"
                            >
                                <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        {/* Sticky Desktop Filter Box */}
                        <div className="rounded-2xl border border-line/90 bg-surface p-5 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.05)]">
                            {/* Sidebar Title */}
                            <div className="flex items-center gap-2 border-b border-line/80 pb-3.5">
                                <svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M4 6h16M7 12h10m-8 6h6" strokeLinecap="round" />
                                </svg>
                                <h2 className="font-display text-xs font-bold uppercase tracking-[0.14em] text-ink">
                                    Filtrer par caractéristiques
                                </h2>
                            </div>

                            <form onSubmit={handleFormSubmit} className="mt-4 space-y-5">
                                {/* Search input */}
                                <div className="relative">
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Rechercher un produit..."
                                        className="h-10 w-full rounded-xl border border-line/90 bg-ground/50 pl-9 pr-8 text-xs text-ink placeholder:text-ink-3/70 transition-all focus:border-accent focus:bg-surface focus:outline-none focus:ring-3 focus:ring-accent/15"
                                    />
                                    <svg
                                        className="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-ink-3"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                    >
                                        <circle cx="9" cy="9" r="6" />
                                        <path d="M13.5 13.5L18 18" strokeLinecap="round" />
                                    </svg>
                                    {searchTerm && (
                                        <button
                                            type="button"
                                            onClick={() => setSearchTerm('')}
                                            className="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-3 hover:text-ink"
                                        >
                                            <svg className="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                    )}
                                </div>

                                {/* SECTION 1 : PRIX */}
                                <div className="border-t border-line/70 pt-3">
                                    <button
                                        type="button"
                                        onClick={() => toggleSection('price')}
                                        className="flex w-full items-center justify-between py-1 text-left"
                                    >
                                        <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                            Prix
                                        </span>
                                        <span className="text-sm font-semibold text-ink-3">
                                            {sections.price ? '−' : '+'}
                                        </span>
                                    </button>

                                    {sections.price && (
                                        <div className="mt-3 space-y-2.5">
                                            {/* Slider preview & bounds */}
                                            <div className="flex items-center justify-between text-[11px] font-semibold text-ink-2">
                                                <span>{money(priceMin || priceBounds.min)} FCFA</span>
                                                <span>{money(priceMax || priceBounds.max)} FCFA</span>
                                            </div>

                                            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    value={priceMin}
                                                    onChange={(e) => setPriceMin(e.target.value)}
                                                    placeholder="Min"
                                                    className="h-8 w-full rounded-lg border border-line bg-surface px-2 text-xs text-ink placeholder:text-ink-3/70 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15"
                                                />
                                                <span className="text-xs text-ink-3">–</span>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    value={priceMax}
                                                    onChange={(e) => setPriceMax(e.target.value)}
                                                    placeholder="Max"
                                                    className="h-8 w-full rounded-lg border border-line bg-surface px-2 text-xs text-ink placeholder:text-ink-3/70 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15"
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* SECTION 2 : CATÉGORIES */}
                                <div className="border-t border-line/70 pt-3">
                                    <button
                                        type="button"
                                        onClick={() => toggleSection('categories')}
                                        className="flex w-full items-center justify-between py-1 text-left"
                                    >
                                        <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                            Catégories
                                        </span>
                                        <span className="text-sm font-semibold text-ink-3">
                                            {sections.categories ? '−' : '+'}
                                        </span>
                                    </button>

                                    {sections.categories && (
                                        <ul className="mt-2.5 space-y-1.5">
                                            {rootCategories.map((c) => {
                                                const isCurrent = category?.id === c.id;
                                                return (
                                                    <li key={c.slug}>
                                                        <button
                                                            type="button"
                                                            onClick={() => apply({ categorie: isCurrent ? undefined : c.slug })}
                                                            className="group flex w-full items-center justify-between text-left text-xs transition-colors"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <span className={`flex h-3.5 w-3.5 items-center justify-center rounded border transition-colors ${
                                                                    isCurrent
                                                                        ? 'border-ink bg-ink text-white'
                                                                        : 'border-line-strong group-hover:border-ink'
                                                                }`}>
                                                                    {isCurrent && (
                                                                        <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2">
                                                                            <path d="M2.5 6l2.5 2.5 5-5" />
                                                                        </svg>
                                                                    )}
                                                                </span>
                                                                <span className={isCurrent ? 'font-bold text-ink' : 'text-ink-2 group-hover:text-ink'}>
                                                                    {c.name}
                                                                </span>
                                                            </div>
                                                            {c.count !== undefined && (
                                                                <span className="text-[10.5px] text-ink-3">
                                                                    ({c.count})
                                                                </span>
                                                            )}
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </div>

                                {/* SECTION 3 : MARQUES / CRÉATEURS */}
                                {vendors.length > 0 && (
                                    <div className="border-t border-line/70 pt-3">
                                        <button
                                            type="button"
                                            onClick={() => toggleSection('vendors')}
                                            className="flex w-full items-center justify-between py-1 text-left"
                                        >
                                            <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                                Marques & Créateurs
                                            </span>
                                            <span className="text-sm font-semibold text-ink-3">
                                                {sections.vendors ? '−' : '+'}
                                            </span>
                                        </button>

                                        {sections.vendors && (
                                            <ul className="mt-2.5 max-h-44 space-y-1.5 overflow-y-auto pr-1">
                                                {vendors.map((v) => {
                                                    const isChecked = q.vendeur === v.slug || String(q.vendeur) === String(v.id);
                                                    return (
                                                        <li key={v.id}>
                                                            <button
                                                                type="button"
                                                                onClick={() => apply({ vendeur: isChecked ? undefined : v.slug })}
                                                                className="group flex w-full items-center gap-2 text-left text-xs transition-colors"
                                                            >
                                                                <span className={`flex h-3.5 w-3.5 items-center justify-center rounded border transition-colors ${
                                                                    isChecked
                                                                        ? 'border-ink bg-ink text-white'
                                                                        : 'border-line-strong group-hover:border-ink'
                                                                }`}>
                                                                    {isChecked && (
                                                                        <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2">
                                                                            <path d="M2.5 6l2.5 2.5 5-5" />
                                                                        </svg>
                                                                    )}
                                                                </span>
                                                                <span className={isChecked ? 'font-bold text-ink truncate' : 'text-ink-2 group-hover:text-ink truncate'}>
                                                                    {v.name}
                                                                </span>
                                                            </button>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        )}
                                    </div>
                                )}

                                {/* SECTION 4 : GENRES / TYPES D'OFFRES */}
                                <div className="border-t border-line/70 pt-3">
                                    <button
                                        type="button"
                                        onClick={() => toggleSection('types')}
                                        className="flex w-full items-center justify-between py-1 text-left"
                                    >
                                        <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                            Types d'offres
                                        </span>
                                        <span className="text-sm font-semibold text-ink-3">
                                            {sections.types ? '−' : '+'}
                                        </span>
                                    </button>

                                    {sections.types && (
                                        <ul className="mt-2.5 space-y-1.5">
                                            {Object.entries(TYPE_CONFIG).map(([key, config]) => {
                                                const isSelected = activeType === key;
                                                return (
                                                    <li key={key}>
                                                        <button
                                                            type="button"
                                                            onClick={() => apply({ type: isSelected ? undefined : key })}
                                                            className="group flex w-full items-center gap-2 text-left text-xs transition-colors"
                                                        >
                                                            <span className={`flex h-3.5 w-3.5 items-center justify-center rounded border transition-colors ${
                                                                isSelected
                                                                    ? 'border-ink bg-ink text-white'
                                                                    : 'border-line-strong group-hover:border-ink'
                                                            }`}>
                                                                {isSelected && (
                                                                    <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2">
                                                                        <path d="M2.5 6l2.5 2.5 5-5" />
                                                                    </svg>
                                                                )}
                                                            </span>
                                                            <span className={isSelected ? 'font-bold text-ink' : 'text-ink-2 group-hover:text-ink'}>
                                                                {config.label}
                                                            </span>
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </div>

                                {/* SECTION 5 : COULEURS (affichée uniquement s'il y a des produits qui ont des couleurs dans le backend) */}
                                {availableColors && availableColors.length > 0 && (
                                    <div className="border-t border-line/70 pt-3">
                                        <button
                                            type="button"
                                            onClick={() => toggleSection('colors')}
                                            className="flex w-full items-center justify-between py-1 text-left"
                                        >
                                            <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                                Couleurs
                                            </span>
                                            <span className="text-sm font-semibold text-ink-3">
                                                {sections.colors ? '−' : '+'}
                                            </span>
                                        </button>

                                        {sections.colors && (
                                            <div className="mt-2.5 grid grid-cols-2 gap-2">
                                                {availableColors.map((colorName) => {
                                                    const isSelected = q.couleur === colorName;
                                                    const hex = getColorHex(colorName);
                                                    return (
                                                        <button
                                                            key={colorName}
                                                            type="button"
                                                            onClick={() => apply({ couleur: isSelected ? undefined : colorName })}
                                                            className={`flex items-center gap-2 rounded-lg border p-1.5 text-left text-[11px] transition-all ${
                                                                isSelected
                                                                    ? 'border-ink bg-ground-alt/70 font-bold text-ink'
                                                                    : 'border-line/70 text-ink-2 hover:border-line-strong hover:text-ink'
                                                            }`}
                                                        >
                                                            <span
                                                                className="h-3.5 w-3.5 shrink-0 rounded-full border border-black/15 shadow-2xs"
                                                                style={{ backgroundColor: hex }}
                                                            />
                                                            <span className="truncate">{colorName}</span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* SECTION 6 : DISPONIBILITÉS */}
                                <div className="border-t border-line/70 pt-3">
                                    <button
                                        type="button"
                                        onClick={() => toggleSection('availability')}
                                        className="flex w-full items-center justify-between py-1 text-left"
                                    >
                                        <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                            Disponibilités
                                        </span>
                                        <span className="text-sm font-semibold text-ink-3">
                                            {sections.availability ? '−' : '+'}
                                        </span>
                                    </button>

                                    {sections.availability && (
                                        <ul className="mt-2.5 space-y-1.5">
                                            <li>
                                                <button
                                                    type="button"
                                                    onClick={() => apply({ dispo: q.dispo === 'promo' ? undefined : 'promo' })}
                                                    className="group flex w-full items-center gap-2 text-left text-xs transition-colors"
                                                >
                                                    <span className={`flex h-3.5 w-3.5 items-center justify-center rounded border transition-colors ${
                                                        q.dispo === 'promo'
                                                            ? 'border-rust bg-rust text-white'
                                                            : 'border-line-strong group-hover:border-rust'
                                                    }`}>
                                                        {q.dispo === 'promo' && (
                                                            <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2">
                                                                <path d="M2.5 6l2.5 2.5 5-5" />
                                                            </svg>
                                                        )}
                                                    </span>
                                                    <span className={q.dispo === 'promo' ? 'font-bold text-rust' : 'text-ink-2 group-hover:text-ink'}>
                                                        Offres en promotion
                                                    </span>
                                                </button>
                                            </li>
                                        </ul>
                                    )}
                                </div>

                                {/* Action Buttons */}
                                <div className="border-t border-line/70 pt-4 space-y-2">
                                    <button
                                        type="submit"
                                        className="w-full rounded-full bg-ink py-2.5 text-xs font-bold uppercase tracking-wider text-white transition-all hover:bg-ink/90 active:scale-[0.99] shadow-xs"
                                    >
                                        Filtrer
                                    </button>

                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={() => router.get(route('marketplace.shop'))}
                                            className="block w-full text-center text-xs font-medium text-ink-3 hover:text-rust transition-colors py-1"
                                        >
                                            Réinitialiser les filtres
                                        </button>
                                    )}
                                </div>
                            </form>
                        </div>
                    </aside>

                    {/* RIGHT CONTENT: Toolbar + 3-Column Grid + 'Voir plus' */}
                    <div>
                        {/* Top Toolbar */}
                        <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line/80 bg-surface px-4 py-3 shadow-2xs">
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-semibold text-ink">
                                    {offers.total}
                                </span>
                                <span className="text-xs text-ink-3">
                                    offre{offers.total > 1 ? 's' : ''} trouvée{offers.total > 1 ? 's' : ''}
                                </span>
                            </div>

                            {/* Sort selector */}
                            <div className="flex items-center gap-2">
                                <label htmlFor="tri-select" className="text-xs font-medium text-ink-3">
                                    Trier par :
                                </label>
                                <select
                                    id="tri-select"
                                    value={q.tri ?? ''}
                                    onChange={(e) => apply({ tri: e.target.value || undefined })}
                                    className="h-9 rounded-xl border border-line bg-surface px-3 pr-8 text-xs font-semibold text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15"
                                >
                                    <option value="">Nouveautés</option>
                                    <option value="prix_asc">Prix : croissant</option>
                                    <option value="prix_desc">Prix : décroissant</option>
                                    <option value="populaire">Les plus populaires</option>
                                </select>
                            </div>
                        </div>

                        {/* Active Filter Chips */}
                        {hasActiveFilters && (
                            <div className="mb-6 flex flex-wrap items-center gap-2">
                                <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-3">
                                    Filtres :
                                </span>
                                {activeChips.map((chip, idx) => (
                                    <span
                                        key={idx}
                                        className="inline-flex items-center gap-1.5 rounded-full border border-line bg-ground-alt/60 px-3 py-1 text-xs font-medium text-ink"
                                    >
                                        <span>{chip.label}</span>
                                        <button
                                            type="button"
                                            onClick={chip.remove}
                                            className="rounded-full p-0.5 text-ink-3 hover:text-rust"
                                            title="Retirer"
                                        >
                                            <svg className="h-3 w-3" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                    </span>
                                ))}
                                <button
                                    type="button"
                                    onClick={() => router.get(route('marketplace.shop'))}
                                    className="ml-1 text-xs font-medium text-ink-3 underline hover:text-rust"
                                >
                                    Effacer tout
                                </button>
                            </div>
                        )}

                        {/* Product Grid (3 Columns on Desktop matching Reference) */}
                        {offers.data.length === 0 ? (
                            <EmptyState
                                icon="search"
                                title="Aucune offre trouvée"
                                text="Aucun article ne correspond à votre sélection actuelle. Essayez d'élargir vos filtres ou de réinitialiser vos critères."
                                cta={
                                    <button
                                        type="button"
                                        onClick={() => router.get(route('marketplace.shop'))}
                                        className="rounded-full bg-ink px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-ink/90"
                                    >
                                        Voir toutes les offres
                                    </button>
                                }
                            />
                        ) : (
                            <>
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                    {offers.data.map((offer) => (
                                        <OfferCard key={offer.id} offer={offer} />
                                    ))}
                                </div>

                                {/* Pagination / "+ Voir plus" Button matching Reference UI */}
                                <div className="mt-12 flex flex-col items-center justify-center gap-4">
                                    {offers.next_page_url ? (
                                        <Link
                                            href={offers.next_page_url}
                                            preserveScroll
                                            className="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-10 py-3 text-xs font-bold uppercase tracking-[0.16em] text-white shadow-md transition-all hover:bg-ink/85 hover:shadow-lg active:scale-[0.98]"
                                        >
                                            <span className="text-sm font-bold">+</span>
                                            <span>Voir plus</span>
                                        </Link>
                                    ) : (
                                        <p className="text-xs text-ink-3">
                                            Vous avez atteint la fin des offres disponibles.
                                        </p>
                                    )}

                                    {/* Numbered Pagination bar for convenience */}
                                    <div className="mt-2 w-full">
                                        <Pagination paginator={offers} />
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Shop.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
