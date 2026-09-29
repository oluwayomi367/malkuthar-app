import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { money } from '../../lib/format';
import { addToCart } from '../../lib/cart';
import { isFavorite, toggleFavorite } from '../../lib/wishlist';
import { getColorHex } from '../../lib/colors';
import RosetteBadge from './RosetteBadge';

const TYPE_LABELS = { digital: 'Digital', physical: 'Physique', service: 'Service' };

export default function ProductCard({ product }) {
    const isAffiliable = product.type === 'digital' && product.is_affiliable;
    const price = product.promotional_price ?? product.price;
    const hasSale = product.promotional_price !== null && product.promotional_price !== undefined && Number(product.promotional_price) < Number(product.price);
    const discountPercent = hasSale ? Math.round(100 - (product.promotional_price / product.price) * 100) : 0;

    const favId = `prod_${product.id}`;
    const [fav, setFav] = useState(false);
    const [added, setAdded] = useState(false);

    useEffect(() => {
        setFav(isFavorite(favId));
        const handleWishlist = () => setFav(isFavorite(favId));
        window.addEventListener('mal-wishlist-changed', handleWishlist);
        return () => window.removeEventListener('mal-wishlist-changed', handleWishlist);
    }, [favId]);

    const handleToggleFav = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const next = toggleFavorite(favId);
        setFav(next);
    };

    const handleAddToCart = (e) => {
        e.preventDefault();
        e.stopPropagation();

        addToCart({
            id: product.id,
            name: product.name,
            price: Number(price),
            quantity: 1,
            type: product.type,
            image: product.main_image ?? null,
        });

        setAdded(true);
        setTimeout(() => setAdded(false), 2000);
    };

    return (
        <div className="group relative flex flex-col rounded-2xl border border-line/80 bg-surface p-4 text-center transition-all duration-300 hover:-translate-y-1 hover:border-accent/40 hover:shadow-[0_16px_36px_-12px_rgba(46,44,40,0.12),0_2px_6px_rgba(46,44,40,0.04)]">
            {/* Top Bar: Rosette Badge or Type & Wishlist Heart */}
            <div className="flex items-start justify-between min-h-[44px] w-full">
                <div>
                    {hasSale ? (
                        <RosetteBadge discount={discountPercent} />
                    ) : isAffiliable ? (
                        <span className="inline-flex items-center gap-1 rounded-full border border-accent/30 bg-accent-soft/60 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-accent">
                            <span className="h-1.5 w-1.5 rounded-full bg-accent" />
                            Affiliable
                        </span>
                    ) : (
                        <span className="inline-flex items-center rounded-full border border-line bg-ground-alt/60 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-ink-3">
                            {TYPE_LABELS[product.type] ?? product.type}
                        </span>
                    )}
                </div>

                <button
                    type="button"
                    onClick={handleToggleFav}
                    aria-label="Ajouter aux favoris"
                    className={`inline-flex h-8 w-8 items-center justify-center rounded-full border transition-all ${
                        fav
                            ? 'border-rust/40 bg-rust/10 text-rust'
                            : 'border-transparent text-ink-3 hover:border-line hover:bg-ground-alt hover:text-ink'
                    }`}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill={fav ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </button>
            </div>

            {/* Product Image Area */}
            <Link
                href={route('catalog.show', product.slug)}
                className="relative my-2 flex aspect-square w-full items-center justify-center overflow-hidden rounded-xl bg-white p-3"
            >
                {product.main_image ? (
                    <img
                        src={product.main_image}
                        alt={product.name}
                        loading="lazy"
                        className="max-h-full max-w-full object-contain transition-transform duration-500 ease-out group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-ground-alt/40 text-line-strong rounded-lg">
                        <svg className="h-12 w-12 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.2">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M3 15l5-5 4 4 3-3 6 6" />
                            <circle cx="9" cy="9" r="1.5" />
                        </svg>
                    </div>
                )}
            </Link>

            {/* Brand / Category Label */}
            <div className="mt-1">
                <span className="inline-block text-[11px] font-bold uppercase tracking-[0.16em] text-ink-3">
                    {product.category || 'MAISON MALKUTHAR'}
                </span>
            </div>

            {/* Product Title */}
            <h3 className="mt-1 line-clamp-2 min-h-[2.5rem] font-display text-[13.5px] font-bold leading-snug text-ink transition-colors group-hover:text-accent">
                <Link href={route('catalog.show', product.slug)}>
                    {product.name}
                </Link>
            </h3>

            {/* Swatches / Indicator Dots */}
            <div className="my-2 flex items-center justify-center gap-1.5 min-h-[16px]">
                {product.colors && product.colors.length > 0 ? (
                    <div className="flex items-center gap-1">
                        {product.colors.slice(0, 5).map((col, idx) => (
                            <span
                                key={idx}
                                title={col}
                                className="h-2 w-2 rounded-full border border-black/15 shadow-2xs"
                                style={{ backgroundColor: getColorHex(col) }}
                            />
                        ))}
                        {product.colors.length > 5 && (
                            <span className="text-[10px] text-ink-3">+{product.colors.length - 5}</span>
                        )}
                    </div>
                ) : null}
            </div>

            {/* Price Block */}
            <div className="mt-auto pt-1 pb-3">
                <div className="flex flex-wrap items-center justify-center gap-2">
                    {hasSale && (
                        <span className="text-xs text-ink-3 line-through">
                            {money(product.price)} {product.currency ?? 'FCFA'}
                        </span>
                    )}
                    <span className={`font-display text-[15.5px] font-bold tracking-tight ${hasSale ? 'text-rust' : 'text-ink'}`}>
                        {money(price)} {product.currency ?? 'FCFA'}
                    </span>
                </div>
            </div>

            {/* Bottom Actions Row: + Ajouter au panier & Wishlist */}
            <div className="flex items-center gap-2 border-t border-line/60 pt-3">
                <button
                    type="button"
                    onClick={handleAddToCart}
                    className={`flex-1 flex items-center justify-center gap-1.5 rounded-full border py-2 px-3 text-xs font-semibold tracking-wide transition-all shadow-2xs ${
                        added
                            ? 'border-emerald-600 bg-emerald-600 text-white'
                            : 'border-line/90 bg-surface text-ink hover:border-ink hover:bg-ink hover:text-white active:scale-[0.98]'
                    }`}
                >
                    {added ? (
                        <>
                            <svg className="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2.5">
                                <path d="M3 8.5l3.5 3.5 6.5-7" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <span>Ajouté !</span>
                        </>
                    ) : (
                        <>
                            <span className="text-sm font-bold leading-none">+</span>
                            <span>Ajouter au panier</span>
                        </>
                    )}
                </button>

                <button
                    type="button"
                    onClick={handleToggleFav}
                    aria-label="Favoris"
                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full border transition-all ${
                        fav
                            ? 'border-rust/40 bg-rust/10 text-rust'
                            : 'border-line/90 bg-surface text-ink-2 hover:border-ink hover:text-rust'
                    }`}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill={fav ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
