import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import ProductCard from '../../Components/commerce/ProductCard';
import { money } from '../../lib/format';
import { getColorHex } from '../../lib/colors';

const TYPE_CONFIG = {
    digital: { label: 'Produits digitaux', shortLabel: 'Digitaux' },
    physical: { label: 'Produits physiques', shortLabel: 'Physiques' },
    service: { label: 'Services & Consultations', shortLabel: 'Services' },
};

export default function ShopIndex({
    products,
    categories = [],
    categoriesWithCount = {},
    priceBounds = { min: 0, max: 100000 },
    availableColors = [],
    type,
    category,
    sort,
    q,
    query = {},
}) {
    // Accordions collapse state
    const [sections, setSections] = useState({
        price: true,
        categories: true,
        types: true,
        colors: true,
        availability: true,
    });

    const toggleSection = (key) => {
        setSections((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    // Filtres locaux
    const [searchTerm, setSearchTerm] = useState(q ?? '');
    const [priceMin, setPriceMin] = useState(query.prix_min ?? '');
    const [priceMax, setPriceMax] = useState(query.prix_max ?? '');
    const [selectedColor, setSelectedColor] = useState('');
    const [mobileFiltersOpen, setMobileFiltersOpen] = useState(false);

    const go = (params = {}) => {
        const payload = {
            type,
            category,
            sort: sort !== 'recent' ? sort : null,
            q: q || null,
            prix_min: query.prix_min,
            prix_max: query.prix_max,
            dispo: query.dispo,
            couleur: query.couleur,
            ...params,
        };

        // Supprime les clés vides
        Object.keys(payload).forEach((k) => {
            if (payload[k] === null || payload[k] === undefined || payload[k] === '') {
                delete payload[k];
            }
        });

        router.get(route('catalog.index'), payload, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        go({
            q: searchTerm.trim() || null,
            prix_min: priceMin !== '' ? priceMin : null,
            prix_max: priceMax !== '' ? priceMax : null,
        });
        setMobileFiltersOpen(false);
    };

    // Active Chips
    const activeChips = [];
    if (q) activeChips.push({ label: `« ${q} »`, remove: () => { setSearchTerm(''); go({ q: null }); } });
    if (type) activeChips.push({ label: TYPE_CONFIG[type]?.label ?? type, remove: () => go({ type: null }) });
    if (category) activeChips.push({ label: `Catégorie : ${category}`, remove: () => go({ category: null }) });
    if (query.prix_min || query.prix_max) {
        activeChips.push({
            label: `Prix : ${query.prix_min ? Number(query.prix_min).toLocaleString('fr-FR') : '0'} – ${query.prix_max ? Number(query.prix_max).toLocaleString('fr-FR') : '∞'} FCFA`,
            remove: () => { setPriceMin(''); setPriceMax(''); go({ prix_min: null, prix_max: null }); },
        });
    }
    if (query.dispo === 'promo') {
        activeChips.push({ label: 'En promotion', remove: () => go({ dispo: null }) });
    }
    if (query.couleur) {
        activeChips.push({ label: `Couleur : ${query.couleur}`, remove: () => go({ couleur: null }) });
    }

    const hasActiveFilters = activeChips.length > 0;

    return (
        <>
            <Head title="La Boutique — MALKUTHAR">
                <meta name="description" content="Découvrez le catalogue officiel de la maison MALKUTHAR : livres, formations et créations artisanales d'exception." />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 py-4 sm:px-6 sm:py-6">
                {/* 1. BANNIÈRE EXACTE SELON LA RÉFÉRENCE */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-5 sm:p-7 md:p-8 lg:p-9 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-6 md:grid-cols-2 lg:gap-10">
                        {/* Côté gauche : Surtitre orange souligné + Titre 2 lignes + Texte descriptif */}
                        <div className="flex flex-col justify-center">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                Catalogue Officiel
                            </span>
                            <h1 className="mt-3 sm:mt-4 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl lg:text-4xl leading-[1.15]">
                                {category ? (
                                    <>
                                        Collection :
                                        <br />
                                        <span className="text-accent">{category}</span>
                                    </>
                                ) : (
                                    <>
                                        Trouvez le produit d'exception
                                        <br />
                                        qui vous correspond
                                    </>
                                )}
                            </h1>
                            <p className="mt-3 max-w-lg text-xs sm:text-sm leading-relaxed text-ink-2">
                                Parcourez les œuvres, guides initiatiques, accessoires sacrés et consultations d'orientation certifiés par la maison MALKUTHAR.
                            </p>
                        </div>

                        {/* Côté droit : Photo encadrée avec ses propres coins arrondis, sans voile blanc */}
                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src="/images/catalog-banner.jpg"
                                alt="Collection MALKUTHAR"
                                className="h-44 sm:h-52 md:h-56 lg:h-60 w-full object-cover object-center"
                            />
                        </div>
                    </div>
                </section>

                {/* Navigation par catégories sous forme de pilules modernes */}
                <div className="mt-4 mb-6 flex flex-wrap items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    {categories.slice(0, 6).map((c) => {
                        const isCurrent = category === c;
                        return (
                            <Link
                                key={c}
                                href={route('catalog.index', { category: c })}
                                className={`inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-semibold transition-all duration-150 ${
                                    isCurrent
                                        ? 'bg-ink text-white shadow-xs'
                                        : 'border border-line/80 bg-surface text-ink-2 hover:border-accent/60 hover:bg-ground hover:text-ink'
                                }`}
                            >
                                {c}
                            </Link>
                        );
                    })}
                </div>

                {/* Mobile Filter Button */}
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
                        {products.total} produit{products.total > 1 ? 's' : ''}
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

                        <div className="rounded-2xl border border-line/90 bg-surface p-5 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.05)]">
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
                                            {categories.map((c) => {
                                                const isCurrent = category === c;
                                                const count = categoriesWithCount[c];
                                                return (
                                                    <li key={c}>
                                                        <button
                                                            type="button"
                                                            onClick={() => go({ category: isCurrent ? null : c })}
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
                                                                    {c}
                                                                </span>
                                                            </div>
                                                            {count !== undefined && (
                                                                <span className="text-[10.5px] text-ink-3">
                                                                    ({count})
                                                                </span>
                                                            )}
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </div>

                                {/* SECTION 3 : NATURE DU PRODUIT */}
                                <div className="border-t border-line/70 pt-3">
                                    <button
                                        type="button"
                                        onClick={() => toggleSection('types')}
                                        className="flex w-full items-center justify-between py-1 text-left"
                                    >
                                        <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-ink">
                                            Types d'articles
                                        </span>
                                        <span className="text-sm font-semibold text-ink-3">
                                            {sections.types ? '−' : '+'}
                                        </span>
                                    </button>

                                    {sections.types && (
                                        <ul className="mt-2.5 space-y-1.5">
                                            {Object.entries(TYPE_CONFIG).map(([key, config]) => {
                                                const isSelected = type === key;
                                                return (
                                                    <li key={key}>
                                                        <button
                                                            type="button"
                                                            onClick={() => go({ type: isSelected ? null : key })}
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

                                {/* SECTION 4 : COULEURS (affichée uniquement s'il y a des produits qui ont des couleurs dans le backend) */}
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
                                                    const isSelected = query.couleur === colorName;
                                                    const hex = getColorHex(colorName);
                                                    return (
                                                        <button
                                                            key={colorName}
                                                            type="button"
                                                            onClick={() => go({ couleur: isSelected ? null : colorName })}
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

                                {/* SECTION 5 : DISPONIBILITÉS */}
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
                                                    onClick={() => go({ dispo: query.dispo === 'promo' ? null : 'promo' })}
                                                    className="group flex w-full items-center gap-2 text-left text-xs transition-colors"
                                                >
                                                    <span className={`flex h-3.5 w-3.5 items-center justify-center rounded border transition-colors ${
                                                        query.dispo === 'promo'
                                                            ? 'border-rust bg-rust text-white'
                                                            : 'border-line-strong group-hover:border-rust'
                                                    }`}>
                                                        {query.dispo === 'promo' && (
                                                            <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="2">
                                                                <path d="M2.5 6l2.5 2.5 5-5" />
                                                            </svg>
                                                        )}
                                                    </span>
                                                    <span className={query.dispo === 'promo' ? 'font-bold text-rust' : 'text-ink-2 group-hover:text-ink'}>
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
                                            onClick={() => router.get(route('catalog.index'))}
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
                                    {products.total}
                                </span>
                                <span className="text-xs text-ink-3">
                                    produit{products.total > 1 ? 's' : ''} disponible{products.total > 1 ? 's' : ''}
                                </span>
                            </div>

                            {/* Sort selector */}
                            <div className="flex items-center gap-2">
                                <label htmlFor="tri-boutique" className="text-xs font-medium text-ink-3">
                                    Trier par :
                                </label>
                                <select
                                    id="tri-boutique"
                                    value={sort ?? 'recent'}
                                    onChange={(e) => go({ sort: e.target.value })}
                                    className="h-9 rounded-xl border border-line bg-surface px-3 pr-8 text-xs font-semibold text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15"
                                >
                                    <option value="recent">Nouveautés</option>
                                    <option value="price_asc">Prix : croissant</option>
                                    <option value="price_desc">Prix : décroissant</option>
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
                                    onClick={() => router.get(route('catalog.index'))}
                                    className="ml-1 text-xs font-medium text-ink-3 underline hover:text-rust"
                                >
                                    Effacer tout
                                </button>
                            </div>
                        )}

                        {/* Product Grid (3 Columns on Desktop matching Reference) */}
                        {products.data.length === 0 ? (
                            <EmptyState
                                icon="search"
                                title="Aucun produit trouvé"
                                text="Aucun article ne correspond à votre sélection actuelle. Essayez d'ajuster vos critères ou votre terme de recherche."
                                cta={
                                    <button
                                        type="button"
                                        onClick={() => router.get(route('catalog.index'))}
                                        className="rounded-full bg-ink px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-ink/90"
                                    >
                                        Réinitialiser la boutique
                                    </button>
                                }
                            />
                        ) : (
                            <>
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                    {products.data.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>

                                {/* Pagination / "+ Voir plus" Button matching Reference UI */}
                                <div className="mt-12 flex flex-col items-center justify-center gap-4">
                                    {products.next_page_url ? (
                                        <Link
                                            href={products.next_page_url}
                                            preserveScroll
                                            className="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-10 py-3 text-xs font-bold uppercase tracking-[0.16em] text-white shadow-md transition-all hover:bg-ink/85 hover:shadow-lg active:scale-[0.98]"
                                        >
                                            <span className="text-sm font-bold">+</span>
                                            <span>Voir plus</span>
                                        </Link>
                                    ) : (
                                        <p className="text-xs text-ink-3">
                                            Vous avez exploré l'ensemble des créations du catalogue.
                                        </p>
                                    )}

                                    <div className="mt-2 w-full">
                                        <Pagination paginator={products} />
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

ShopIndex.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
