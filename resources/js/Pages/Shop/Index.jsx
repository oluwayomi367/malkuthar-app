import { Head, Link, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import Select from '../../Components/ui/Select';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import ProductCard from '../../Components/commerce/ProductCard';

const TYPE_TITLES = { digital: 'Produits digitaux', physical: 'Produits physiques', service: 'Services' };
const TYPE_INTROS = {
    digital: 'Ebooks, formations, audios — livraison immédiate après paiement.',
    physical: 'Livres, coffrets, accessoires — expédition suivie.',
    service: 'Consultations et séances, réservées sur créneau.',
};

export default function ShopIndex({ products, categories, type, category, sort }) {
    const heading = TYPE_TITLES[type] ?? 'La boutique';

    const natureTabs = [
        { key: null, label: 'Tout' },
        { key: 'digital', label: 'Digitaux' },
        { key: 'physical', label: 'Physiques' },
        { key: 'service', label: 'Services' },
    ];

    const go = (params) => {
        router.get(route('catalog.index'), { type, category, sort: sort !== 'recent' ? sort : null, ...params }, {
            preserveState: true, preserveScroll: true, replace: true,
        });
    };

    return (
        <>
            <Head title={heading}>
                <meta name="description" content={`Parcourez le catalogue MALKUTHAR : ${heading}.`} />
            </Head>

            <div className="relative overflow-hidden">
                <div className="relative mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-12">
                    <header className="mb-8 border-b border-black/[0.06] pb-6">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Catalogue</p>
                        <h1 className="mt-2 font-display text-3xl font-bold tracking-[-0.01em] text-ink sm:text-4xl">{heading}</h1>
                        <p className="mt-2 text-sm text-ink-2">
                            {TYPE_INTROS[type] ?? 'Digital, physique et service — tout MALKUTHAR au même endroit.'}
                            <span className="text-ink-3"> · {products.total} produit{products.total > 1 ? 's' : ''}</span>
                        </p>
                    </header>

                    <nav className="flex gap-1 overflow-x-auto rounded-xl border border-black/10 bg-surface p-1 backdrop-blur">
                        {natureTabs.map((t) => (
                            <Link key={t.label} href={route('catalog.index', { type: t.key, category, sort: sort !== 'recent' ? sort : null })}
                                  preserveScroll
                                  className={['shrink-0 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                      (t.key ?? null) === (type ?? null) ? 'bg-accent text-white shadow-[0_6px_20px_-8px] shadow-black/10' : 'text-ink-3 hover:text-ink'].join(' ')}>
                                {t.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="mt-4 flex flex-wrap gap-3">
                        <div className="min-w-[160px] flex-1 sm:max-w-[220px]">
                            <Select name="category" placeholder="Toutes les catégories" value={category ?? ''}
                                    options={categories.reduce((acc, c) => ({ ...acc, [c]: c }), {})}
                                    onChange={(e) => go({ category: e.target.value || null })} />
                        </div>
                        <div className="min-w-[150px] sm:max-w-[190px]">
                            <Select name="sort" value={sort} onChange={(e) => go({ sort: e.target.value })}
                                    options={{ recent: 'Plus récents', price_asc: 'Prix croissant', price_desc: 'Prix décroissant' }} />
                        </div>
                    </div>

                    <div className="mt-8">
                        {products.data.length === 0 ? (
                            <EmptyState title="Aucun produit ne correspond" text="Essayez de retirer un filtre ou de changer de catégorie."
                                        cta={<Button variant="secondary" href={route('catalog.index')}>Réinitialiser</Button>} />
                        ) : (
                            <>
                                <div className="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
                                    {products.data.map((product) => <ProductCard key={product.id} product={product} />)}
                                </div>
                                <Pagination paginator={products} />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

ShopIndex.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
