import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Button from '../../Components/ui/Button';
import OfferCard from '../../Components/commerce/OfferCard';
import {
    CatalogHeader, Segments, FilterPanel, FilterGroup, SearchField, PriceRange,
    CheckOption, ColorOptions, CatalogToolbar, ActiveFilters, LoadMore,
} from '../../Components/commerce/CatalogFilters';

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

            <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-8 sm:px-6 sm:pt-12">
                <CatalogHeader
                    title={category?.name ?? activePoleCategory?.name ?? 'Place de marché'}
                    intro="Les offres des créateurs, artisans, résidences et praticiens partenaires, vérifiées avant publication."
                >
                    {poles.length > 0 && (
                        <div className="mt-6">
                            <Segments
                                label="Univers"
                                items={poles.map((p) => {
                                    const isAll = !p.slug || p.key === 'all';
                                    const isActive = isAll ? !activePole : activePole === p.slug;
                                    return {
                                        key: p.slug ?? 'all',
                                        label: p.label || p.name || 'Univers',
                                        active: isActive,
                                        onClick: () => apply({ pole: isAll ? undefined : (isActive ? undefined : p.slug), categorie: undefined }),
                                    };
                                })}
                            />
                        </div>
                    )}
                </CatalogHeader>

                <div className="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-10">
                    <FilterPanel
                        open={mobileFiltersOpen}
                        onClose={() => setMobileFiltersOpen(false)}
                        onSubmit={handleFormSubmit}
                        hasActive={hasActiveFilters}
                        onReset={resetAll}
                    >
                        <SearchField value={searchTerm} onChange={setSearchTerm} onClear={() => setSearchTerm('')} placeholder="Nom d'une offre" />

                        <FilterGroup title="Prix">
                            <PriceRange min={priceMin} max={priceMax} onMin={setPriceMin} onMax={setPriceMax} bounds={priceBounds} />
                        </FilterGroup>

                        {rootCategories.length > 0 && (
                            <FilterGroup title="Catégories">
                                <ul>
                                    {rootCategories.map((c) => {
                                        const isCurrent = category?.id === c.id;
                                        return (
                                            <li key={c.slug}>
                                                <CheckOption checked={isCurrent} label={c.name} count={c.count}
                                                             onClick={() => apply({ categorie: isCurrent ? undefined : c.slug })} />
                                            </li>
                                        );
                                    })}
                                </ul>
                            </FilterGroup>
                        )}

                        <FilterGroup title="Type d'offre">
                            <ul>
                                {Object.entries(TYPE_CONFIG).map(([key, config]) => {
                                    const isSelected = activeType === key;
                                    return (
                                        <li key={key}>
                                            <CheckOption checked={isSelected} label={config.label}
                                                         onClick={() => apply({ type: isSelected ? undefined : key })} />
                                        </li>
                                    );
                                })}
                            </ul>
                        </FilterGroup>

                        {vendors.length > 0 && (
                            <FilterGroup title="Créateurs">
                                <ul className="max-h-64 overflow-y-auto pr-1">
                                    {vendors.map((v) => {
                                        const isChecked = q.vendeur === v.slug || String(q.vendeur) === String(v.id);
                                        return (
                                            <li key={v.id}>
                                                <CheckOption checked={isChecked} label={v.name}
                                                             onClick={() => apply({ vendeur: isChecked ? undefined : v.slug })} />
                                            </li>
                                        );
                                    })}
                                </ul>
                            </FilterGroup>
                        )}

                        {availableColors && availableColors.length > 0 && (
                            <FilterGroup title="Couleurs">
                                <ColorOptions colors={availableColors} selected={q.couleur}
                                              onToggle={(name) => apply({ couleur: q.couleur === name ? undefined : name })} />
                            </FilterGroup>
                        )}

                        <FilterGroup title="Disponibilité">
                            <CheckOption checked={q.dispo === 'promo'} label="Offres en promotion"
                                         onClick={() => apply({ dispo: q.dispo === 'promo' ? undefined : 'promo' })} />
                        </FilterGroup>
                    </FilterPanel>

                    <div className="min-w-0 space-y-5">
                        <CatalogToolbar
                            countText={`${count} offre${count > 1 ? 's' : ''}`}
                            sortId="tri-select"
                            sortValue={q.tri ?? ''}
                            onSort={(v) => apply({ tri: v || undefined })}
                            options={[['', 'Nouveautés'], ['prix_asc', 'Prix croissant'], ['prix_desc', 'Prix décroissant'], ['populaire', 'Les plus populaires']]}
                            onOpenFilters={() => setMobileFiltersOpen(true)}
                            activeCount={activeChips.length}
                        />

                        <ActiveFilters chips={activeChips} onClear={resetAll} />

                        {offers.data.length === 0 ? (
                            <EmptyState
                                icon="search"
                                title="Aucune offre trouvée"
                                text="Aucune offre ne correspond à ces critères. Modifiez ou retirez un filtre."
                                cta={<Button variant="secondary" onClick={resetAll}>Voir toutes les offres</Button>}
                            />
                        ) : (
                            <>
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                    {offers.data.map((offer) => (
                                        <OfferCard key={offer.id} offer={offer} />
                                    ))}
                                </div>
                                <LoadMore paginator={offers} endText="Vous avez vu toutes les offres." />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Shop.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
