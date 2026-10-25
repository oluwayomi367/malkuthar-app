import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { money } from '../../lib/format';
import { getColorHex } from '../../lib/colors';

function HeartIcon({ filled }) {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill={filled ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
    );
}

function CartIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M3 4h2l2.2 10.2a1.5 1.5 0 001.5 1.2h8.6a1.5 1.5 0 001.5-1.2L20.5 8H6.2" />
            <circle cx="9.5" cy="19.5" r="1.3" /><circle cx="17" cy="19.5" r="1.3" />
        </svg>
    );
}

/**
 * Carte « vitrine » de la boutique : fond blanc, visuel centré et contenu,
 * nom en capitales, prix centré, bouton panier pleine largeur.
 * Même interface de props que CatalogCard, pour être interchangeable.
 */
export default function ShowcaseCard({
    href,
    title,
    image,
    seller = null,      // { label, href?, house?: bool } — affiché seulement pour un vendeur tiers
    price,
    compareAt = null,
    currency = 'FCFA',
    discount = 0,
    colors = [],
    meta = null,
    fav = false,
    onToggleFav,
    onAdd,
    addLabel = 'Ajouter au panier',
    adding = false,
    added = false,
}) {
    const [broken, setBroken] = useState(false);

    return (
        <article className="group/card relative flex h-full flex-col items-center border border-line bg-surface p-4 shadow-xs transition-all duration-300 hover:-translate-y-2 hover:border-deep">
            {discount > 0 && (
                <span className="absolute left-3 top-3 z-10 flex h-14 w-14 items-center justify-center rounded-full bg-rust text-sm font-bold text-white transition-transform duration-300 group-hover/card:scale-105">
                    <span className="sr-only">Remise de </span>-{discount}%
                </span>
            )}

            <button
                type="button"
                onClick={onToggleFav}
                aria-label={fav ? `Retirer « ${title} » des favoris` : `Ajouter « ${title} » aux favoris`}
                aria-pressed={fav}
                className={['absolute right-2 top-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full transition-transform duration-100 hover:scale-110',
                    fav ? 'text-rust' : 'text-ink-3 hover:text-ink'].join(' ')}
            >
                <HeartIcon filled={fav} />
            </button>

            <Link href={href} tabIndex={-1} aria-hidden="true" className="flex aspect-square w-full items-center justify-center overflow-hidden py-2">
                {image && !broken ? (
                    <img src={image} alt="" loading="lazy" onError={() => setBroken(true)}
                         className="max-h-full max-w-full object-contain transition-transform duration-300 group-hover/card:scale-110" draggable={false} />
                ) : (
                    <svg className="h-12 w-12 text-line-strong" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M3 15l5-5 4 4 3-3 6 6" />
                        <circle cx="9" cy="9" r="1.5" />
                    </svg>
                )}
            </Link>

            {seller?.label && !seller.house && (
                seller.href ? (
                    <Link href={seller.href} className="mt-2 max-w-full truncate text-xs font-semibold uppercase tracking-wide text-ink-3 hover:text-ink hover:underline">
                        {seller.label}
                    </Link>
                ) : (
                    <p className="mt-2 max-w-full truncate text-xs font-semibold uppercase tracking-wide text-ink-3">{seller.label}</p>
                )
            )}

            <h3 className="my-2 line-clamp-1 w-full text-center text-sm uppercase text-ink">
                <Link href={href} className="hover:underline">{title}</Link>
            </h3>

            {(colors.length > 0 || meta) && (
                <div className="mb-2 flex flex-wrap items-center justify-center gap-2 text-center text-xs text-ink-2">
                    {colors.length > 0 && (
                        <span className="inline-flex items-center gap-1.5" aria-label={`Coloris : ${colors.join(', ')}`}>
                            {colors.slice(0, 5).map((col, i) => (
                                <span key={`${col}-${i}`} title={col} className="h-3.5 w-3.5 rounded-full border-2 border-line"
                                      style={{ backgroundColor: getColorHex(col) }} />
                            ))}
                            {colors.length > 5 && <span>+{colors.length - 5}</span>}
                        </span>
                    )}
                    {meta}
                </div>
            )}

            <div className="mt-auto flex flex-col items-center">
                {discount > 0 && compareAt !== null && (
                    <p className="text-xs text-ink line-through">
                        <span className="sr-only">Prix initial : </span>{money(compareAt)} {currency}
                    </p>
                )}
                <p className="whitespace-nowrap text-center text-lg font-medium text-ink">
                    {money(price)} {currency}
                </p>
            </div>

            <div className="flex w-full items-center pb-1 pt-4">
                <button
                    type="button"
                    onClick={onAdd}
                    disabled={adding}
                    aria-label={added ? `« ${title} » ajouté au panier` : `${addLabel} : ${title}`}
                    className={['group/btn flex min-h-12 flex-1 items-center justify-center gap-2 rounded-md px-4 text-sm text-ink transition-all duration-300 disabled:opacity-50',
                        added ? 'bg-line-strong' : 'bg-ground hover:bg-ground-alt'].join(' ')}
                >
                    {added ? (
                        <>
                            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                            <span className="line-clamp-1 font-medium">Ajouté au panier</span>
                        </>
                    ) : (
                        <>
                            <CartIcon className="h-5 w-5 transition-all duration-100 group-hover/btn:-rotate-10 group-hover/btn:scale-110" />
                            <span className="line-clamp-1">{addLabel}</span>
                        </>
                    )}
                </button>
            </div>
            <span className="sr-only" aria-live="polite">{added ? 'Ajouté au panier' : ''}</span>
        </article>
    );
}
