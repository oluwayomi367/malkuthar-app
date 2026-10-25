import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import { useImages } from '../../lib/images';
import EmptyState from '../../Components/ui/EmptyState';
import Button from '../../Components/ui/Button';
import OfferCard from '../../Components/commerce/OfferCard';
import {
    ShopBreadcrumb, ShopBanner, SortMenu, ShopFilterPanel, ShopFilterGroup, ShopSearchField,
    ShopPriceRange, ShopCheckOption, ShopColorOptions, ShopActiveFilters, ShopLoadMore,
} from '../../Components/commerce/ShopFilters';

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
    const img = useImages();
    const q = query ?? {};

    // Filtres locaux de formulaire
    const [searchTerm, setSearchTerm] = useState(q.q ?? '');
    const [priceMin, setPriceMin] = useState(q.prix_min ?? '');
    const [priceMax, setPriceMax] = useState(q.prix_max ?? '');
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

    const resetAll = () => router.get(route('marketplace.shop'));
    const count = offers.total;

    return (
        <>
            <Head title={`Place de marché${activePoleCategory ? ` — ${activePoleCategory.name}` : ''}${category ? ` — ${category.name}` : ''}`}>
                <meta name="description" content="Les offres des créateurs, résidences, artisans et praticiens partenaires de MALKUTHAR." />
            </Head>

            <div className="bg-surface">
                <div className="mx-auto max-w-[1440px] px-4 pb-16 sm:px-6">
                    <ShopBreadcrumb items={[
                        { label: 'Accueil', href: '/' },
                        ...((category || activePoleCategory)
                            ? [{ label: 'Place de marché', href: route('marketplace.shop') }, { label: category?.name ?? activePoleCategory.name }]
                            : [{ label: 'Place de marché' }]),
                    ]} />

                    <ShopBanner
                        title={category?.name ?? activePoleCategory?.name ?? 'Place de marché'}
                        intro="Les offres des créateurs, artisans, résidences et praticiens partenaires, vérifiées avant publication."
                        image={img('marketplace.banner')}
                    />

                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <p className="py-2 text-sm text-ink-2" aria-live="polite">{count} offre{count > 1 ? 's' : ''}</p>
                        <div className="w-full lg:w-auto">
                            <SortMenu
                                value={q.tri ?? ''}
                                onChange={(v) => apply({ tri: v || undefined })}
                                options={[['', 'Nouveautés'], ['prix_asc', 'Prix croissant'], ['prix_desc', 'Prix décroissant'], ['populaire', 'Les plus populaires']]}
                            />
                        </div>
                    </div>

                    <div className="mt-4 lg:flex lg:gap-6">
                        <div className="w-full lg:w-[25%] lg:shrink-0">
                            <ShopFilterPanel
                                open={mobileFiltersOpen}
                                onOpen={() => setMobileFiltersOpen(true)}
                                onClose={() => setMobileFiltersOpen(false)}
                                onSubmit={handleFormSubmit}
                                hasActive={hasActiveFilters}
                                onReset={resetAll}
                                activeCount={activeChips.length}
                            >
                                <ShopSearchField value={searchTerm} onChange={setSearchTerm} onClear={() => setSearchTerm('')} placeholder="Rechercher une offre..." />

                                {poles.length > 0 && (
                                    <ShopFilterGroup title="Univers" badge={activePole ? 1 : 0}>
                                        {poles.filter((p) => p.slug && p.key !== 'all').map((p) => {
                                            const isActive = activePole === p.slug;
                                            return (
                                                <ShopCheckOption key={p.slug} checked={isActive} label={p.label || p.name || 'Univers'}
                                                                 onClick={() => apply({ pole: isActive ? undefined : p.slug, categorie: undefined })} />
                                            );
                                        })}
                                    </ShopFilterGroup>
                                )}

                                <ShopFilterGroup title="Prix" badge={(q.prix_min || q.prix_max) ? 1 : 0}>
                                    <ShopPriceRange min={priceMin} max={priceMax} onMin={setPriceMin} onMax={setPriceMax} bounds={priceBounds} />
                                </ShopFilterGroup>

                                {rootCategories.length > 0 && (
                                    <ShopFilterGroup title="Catégories" badge={category ? 1 : 0}>
                                        {rootCategories.map((c) => {
                                            const isCurrent = category?.id === c.id;
                                            return (
                                                <ShopCheckOption key={c.slug} checked={isCurrent} label={c.name} count={c.count}
                                                                 onClick={() => apply({ categorie: isCurrent ? undefined : c.slug })} />
                                            );
                                        })}
                                    </ShopFilterGroup>
                                )}

                                <ShopFilterGroup title="Type d'offre" badge={activeType ? 1 : 0}>
                                    {Object.entries(TYPE_CONFIG).map(([key, config]) => {
                                        const isSelected = activeType === key;
                                        return (
                                            <ShopCheckOption key={key} checked={isSelected} label={config.label}
                                                             onClick={() => apply({ type: isSelected ? undefined : key })} />
                                        );
                                    })}
                                </ShopFilterGroup>

                                {vendors.length > 0 && (
                                    <ShopFilterGroup title="Créateurs" badge={q.vendeur ? 1 : 0}>
                                        <div className="max-h-64 overflow-y-auto pr-1">
                                            {vendors.map((v) => {
                                                const isChecked = q.vendeur === v.slug || String(q.vendeur) === String(v.id);
                                                return (
                                                    <ShopCheckOption key={v.id} checked={isChecked} label={v.name}
                                                                     onClick={() => apply({ vendeur: isChecked ? undefined : v.slug })} />
                                                );
                                            })}
                                        </div>
                                    </ShopFilterGroup>
                                )}

                                {availableColors && availableColors.length > 0 && (
                                    <ShopFilterGroup title="Couleurs" badge={q.couleur ? 1 : 0}>
                                        <ShopColorOptions colors={availableColors} selected={q.couleur}
                                                          onToggle={(name) => apply({ couleur: q.couleur === name ? undefined : name })} />
                                    </ShopFilterGroup>
                                )}

                                <ShopFilterGroup title="Disponibilité" badge={q.dispo === 'promo' ? 1 : 0}>
                                    <ShopCheckOption checked={q.dispo === 'promo'} label="Offres en promotion"
                                                     onClick={() => apply({ dispo: q.dispo === 'promo' ? undefined : 'promo' })} />
                                </ShopFilterGroup>
                            </ShopFilterPanel>
                        </div>

                        <div className="min-w-0 flex-1 space-y-6">
                            <ShopActiveFilters chips={activeChips} onClear={resetAll} />

                            {offers.data.length === 0 ? (
                                <EmptyState
                                    icon="search"
                                    title="Aucune offre trouvée"
                                    text="Aucune offre ne correspond à ces critères. Modifiez ou retirez un filtre."
                                    cta={<Button variant="secondary" onClick={resetAll}>Voir toutes les offres</Button>}
                                />
                            ) : (
                                <>
                                    <div className="grid auto-rows-max grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                        {offers.data.map((offer) => (
                                            <OfferCard key={offer.id} offer={offer} variant="showcase" />
                                        ))}
                                    </div>
                                    <ShopLoadMore paginator={offers} endText="Vous avez vu toutes les offres." />
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Shop.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
