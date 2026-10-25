import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import { useImages } from '../../lib/images';
import EmptyState from '../../Components/ui/EmptyState';
import Button from '../../Components/ui/Button';
import ProductCard from '../../Components/commerce/ProductCard';
import {
    ShopBreadcrumb, ShopBanner, SortMenu, ShopFilterPanel, ShopFilterGroup, ShopSearchField,
    ShopPriceRange, ShopCheckOption, ShopColorOptions, ShopActiveFilters, ShopLoadMore,
} from '../../Components/commerce/ShopFilters';

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
    const img = useImages();
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

            <div className="bg-surface">
                <div className="mx-auto max-w-[1440px] px-4 pb-16 sm:px-6">
                    <ShopBreadcrumb items={[
                        { label: 'Accueil', href: '/' },
                        ...(category
                            ? [{ label: 'Boutique', href: route('catalog.index') }, { label: category }]
                            : [{ label: 'Boutique' }]),
                    ]} />

                    <ShopBanner
                        title={category ?? 'La boutique'}
                        intro={category
                            ? 'Les produits de la maison MALKUTHAR dans cette catégorie.'
                            : 'Livres, formations, objets et consultations proposés par la maison MALKUTHAR.'}
                        image={img('shop.banner')}
                    />

                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <p className="py-2 text-sm text-ink-2" aria-live="polite">{count} produit{count > 1 ? 's' : ''}</p>
                        <div className="w-full lg:w-auto">
                            <SortMenu
                                value={sort ?? 'recent'}
                                onChange={(v) => go({ sort: v })}
                                options={[['recent', 'Nouveautés'], ['price_asc', 'Prix croissant'], ['price_desc', 'Prix décroissant']]}
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
                                <ShopSearchField value={searchTerm} onChange={setSearchTerm} onClear={() => setSearchTerm('')} />

                                <ShopFilterGroup title="Type" badge={type ? 1 : 0}>
                                    {Object.entries(TYPE_CONFIG).map(([key, config]) => (
                                        <ShopCheckOption key={key} checked={type === key} label={config.label}
                                                         onClick={() => go({ type: type === key ? null : key })} />
                                    ))}
                                </ShopFilterGroup>

                                <ShopFilterGroup title="Prix" badge={(query.prix_min || query.prix_max) ? 1 : 0}>
                                    <ShopPriceRange min={priceMin} max={priceMax} onMin={setPriceMin} onMax={setPriceMax} bounds={priceBounds} />
                                </ShopFilterGroup>

                                {categories.length > 0 && (
                                    <ShopFilterGroup title="Catégories" badge={category ? 1 : 0}>
                                        {categories.map((c) => {
                                            const isCurrent = category === c;
                                            return (
                                                <ShopCheckOption key={c} checked={isCurrent} label={c} count={categoriesWithCount[c]}
                                                                 onClick={() => go({ category: isCurrent ? null : c })} />
                                            );
                                        })}
                                    </ShopFilterGroup>
                                )}

                                {availableColors && availableColors.length > 0 && (
                                    <ShopFilterGroup title="Couleurs" badge={query.couleur ? 1 : 0}>
                                        <ShopColorOptions colors={availableColors} selected={query.couleur}
                                                          onToggle={(name) => go({ couleur: query.couleur === name ? null : name })} />
                                    </ShopFilterGroup>
                                )}

                                <ShopFilterGroup title="Disponibilité" badge={query.dispo === 'promo' ? 1 : 0}>
                                    <ShopCheckOption checked={query.dispo === 'promo'} label="Offres en promotion"
                                                     onClick={() => go({ dispo: query.dispo === 'promo' ? null : 'promo' })} />
                                </ShopFilterGroup>
                            </ShopFilterPanel>
                        </div>

                        <div className="min-w-0 flex-1 space-y-6">
                            <ShopActiveFilters chips={activeChips} onClear={resetAll} />

                            {products.data.length === 0 ? (
                                <EmptyState
                                    icon="search"
                                    title="Aucun produit trouvé"
                                    text="Aucun produit ne correspond à ces critères. Modifiez ou retirez un filtre."
                                    cta={<Button variant="secondary" onClick={resetAll}>Réinitialiser les filtres</Button>}
                                />
                            ) : (
                                <>
                                    <div className="grid auto-rows-max grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                        {products.data.map((product) => (
                                            <ProductCard key={product.id} product={product} variant="showcase" />
                                        ))}
                                    </div>
                                    <ShopLoadMore paginator={products} endText="Vous avez vu tous les produits." />
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

ShopIndex.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
