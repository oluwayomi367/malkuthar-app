import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Button from '../../Components/ui/Button';
import ProductCard from '../../Components/commerce/ProductCard';
import {
    CatalogHeader, Segments, FilterPanel, FilterGroup, SearchField, PriceRange,
    CheckOption, ColorOptions, CatalogToolbar, ActiveFilters, LoadMore,
} from '../../Components/commerce/CatalogFilters';

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
    // Filtres locaux
    const [searchTerm, setSearchTerm] = useState(q ?? '');
    const [priceMin, setPriceMin] = useState(query.prix_min ?? '');
    const [priceMax, setPriceMax] = useState(query.prix_max ?? '');
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

    const resetAll = () => router.get(route('catalog.index'));
    const count = products.total;

    return (
        <>
            <Head title="La Boutique — MALKUTHAR">
                <meta name="description" content="Le catalogue de la maison MALKUTHAR : livres, formations, objets et consultations." />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-8 sm:px-6 sm:pt-12">
                <CatalogHeader
                    title={category ?? 'La boutique'}
                    intro={category
                        ? 'Les produits de la maison MALKUTHAR dans cette catégorie.'
                        : 'Livres, formations, objets et consultations proposés par la maison MALKUTHAR.'}
                >
                    <div className="mt-6">
                        <Segments
                            label="Type de produit"
                            items={[
                                { key: 'all', label: 'Tout', active: !type, onClick: () => go({ type: null }) },
                                ...Object.entries(TYPE_CONFIG).map(([key, config]) => ({
                                    key, label: config.shortLabel, active: type === key,
                                    onClick: () => go({ type: type === key ? null : key }),
                                })),
                            ]}
                        />
                    </div>
                </CatalogHeader>

                <div className="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-10">
                    <FilterPanel
                        open={mobileFiltersOpen}
                        onClose={() => setMobileFiltersOpen(false)}
                        onSubmit={handleFormSubmit}
                        hasActive={hasActiveFilters}
                        onReset={resetAll}
                    >
                        <SearchField value={searchTerm} onChange={setSearchTerm} onClear={() => setSearchTerm('')} placeholder="Nom d'un produit" />

                        <FilterGroup title="Prix">
                            <PriceRange min={priceMin} max={priceMax} onMin={setPriceMin} onMax={setPriceMax} bounds={priceBounds} />
                        </FilterGroup>

                        {categories.length > 0 && (
                            <FilterGroup title="Catégories">
                                <ul>
                                    {categories.map((c) => {
                                        const isCurrent = category === c;
                                        return (
                                            <li key={c}>
                                                <CheckOption checked={isCurrent} label={c} count={categoriesWithCount[c]}
                                                             onClick={() => go({ category: isCurrent ? null : c })} />
                                            </li>
                                        );
                                    })}
                                </ul>
                            </FilterGroup>
                        )}

                        {availableColors && availableColors.length > 0 && (
                            <FilterGroup title="Couleurs">
                                <ColorOptions colors={availableColors} selected={query.couleur}
                                              onToggle={(name) => go({ couleur: query.couleur === name ? null : name })} />
                            </FilterGroup>
                        )}

                        <FilterGroup title="Disponibilité">
                            <CheckOption checked={query.dispo === 'promo'} label="Offres en promotion"
                                         onClick={() => go({ dispo: query.dispo === 'promo' ? null : 'promo' })} />
                        </FilterGroup>
                    </FilterPanel>

                    <div className="min-w-0 space-y-5">
                        <CatalogToolbar
                            countText={`${count} produit${count > 1 ? 's' : ''}`}
                            sortId="tri-boutique"
                            sortValue={sort ?? 'recent'}
                            onSort={(v) => go({ sort: v })}
                            options={[['recent', 'Nouveautés'], ['price_asc', 'Prix croissant'], ['price_desc', 'Prix décroissant']]}
                            onOpenFilters={() => setMobileFiltersOpen(true)}
                            activeCount={activeChips.length}
                        />

                        <ActiveFilters chips={activeChips} onClear={resetAll} />

                        {products.data.length === 0 ? (
                            <EmptyState
                                icon="search"
                                title="Aucun produit trouvé"
                                text="Aucun produit ne correspond à ces critères. Modifiez ou retirez un filtre."
                                cta={<Button variant="secondary" onClick={resetAll}>Réinitialiser les filtres</Button>}
                            />
                        ) : (
                            <>
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                    {products.data.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>
                                <LoadMore paginator={products} endText="Vous avez vu tous les produits." />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

ShopIndex.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
