import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { money } from '../../lib/format';
import { getColorHex } from '../../lib/colors';
import RosetteBadge from './RosetteBadge';

function HeartIcon({ filled }) {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill={filled ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
    );
}

/**
 * Gabarit commun aux cartes de la boutique maison (ProductCard) et de la
 * place de marché (OfferCard). Purement visuel : la logique reste dans
 * chaque carte.
 */
export default function CatalogCard({
    href,
    title,
    image,
    seller,             // { label, href?, house?: bool }
    typeLabel,
    price,
    compareAt = null,
    currency = 'FCFA',
    discount = 0,
    colors = [],
    meta = null,        // contenu libre sous le titre (note, « Affiliable »…)
    fav = false,
    onToggleFav,
    onAdd,
    addLabel = 'Ajouter',
    adding = false,
    added = false,
}) {
    // Image absente ou introuvable : on affiche le pictogramme plutôt qu'une image cassée.
    const [broken, setBroken] = useState(false);

    return (
        <article className="relative flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_1px_2px_rgba(28,25,23,0.04)] transition-colors duration-200 hover:border-line-strong">
            <Link href={href} className="relative block aspect-[4/3] overflow-hidden bg-ground-alt" tabIndex={-1} aria-hidden="true">
                {image && !broken ? (
                    <img src={image} alt="" loading="lazy" onError={() => setBroken(true)} className="h-full w-full object-cover" />
                ) : (
                    <span className="flex h-full w-full items-center justify-center text-ink-3">
                        <svg className="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M3 15l5-5 4 4 3-3 6 6" />
                            <circle cx="9" cy="9" r="1.5" />
                        </svg>
                    </span>
                )}
            </Link>

            {discount > 0 && (
                <span className="absolute left-3 top-3">
                    <RosetteBadge discount={discount} />
                </span>
            )}

            <button
                type="button"
                onClick={onToggleFav}
                aria-label={fav ? `Retirer « ${title} » des favoris` : `Ajouter « ${title} » aux favoris`}
                aria-pressed={fav}
                className={[
                    'absolute right-2 top-2 inline-flex h-11 w-11 items-center justify-center rounded-full border bg-surface transition-colors',
                    fav ? 'border-rust/30 text-rust' : 'border-line text-ink-2 hover:border-line-strong hover:text-ink',
                ].join(' ')}
            >
                <HeartIcon filled={fav} />
            </button>

            <div className="flex flex-1 flex-col p-4">
                <div className="flex flex-wrap items-center gap-1.5">
                    {seller?.href ? (
                        <Link href={seller.href}
                              className={['inline-flex max-w-full items-center truncate rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors',
                                  seller.house ? 'border-accent/25 bg-accent-soft text-accent-2' : 'border-line-strong bg-ground-alt text-ink hover:border-ink'].join(' ')}>
                            <span className="truncate">{seller.label}</span>
                        </Link>
                    ) : seller?.label ? (
                        <span className={['inline-flex max-w-full items-center rounded-full border px-2 py-0.5 text-xs font-semibold',
                            seller.house ? 'border-accent/25 bg-accent-soft text-accent-2' : 'border-line-strong bg-ground-alt text-ink'].join(' ')}>
                            <span className="truncate">{seller.label}</span>
                        </span>
                    ) : null}
                    {typeLabel && (
                        <span className="inline-flex items-center rounded-full border border-line px-2 py-0.5 text-xs font-medium text-ink-2">
                            {typeLabel}
                        </span>
                    )}
                </div>

                <h3 className="mt-3 line-clamp-2 text-base font-semibold leading-snug text-ink">
                    <Link href={href} className="hover:underline hover:decoration-line-strong hover:underline-offset-4">
                        {title}
                    </Link>
                </h3>

                {(colors.length > 0 || meta) && (
                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-3">
                        {colors.length > 0 && (
                            <span className="inline-flex items-center gap-1" aria-label={`Coloris : ${colors.join(', ')}`}>
                                {colors.slice(0, 5).map((col, i) => (
                                    <span key={`${col}-${i}`} title={col} className="h-3 w-3 rounded-full border border-ink/15"
                                          style={{ backgroundColor: getColorHex(col) }} />
                                ))}
                                {colors.length > 5 && <span className="ml-0.5">+{colors.length - 5}</span>}
                            </span>
                        )}
                        {meta}
                    </div>
                )}

                <div className="mt-auto flex items-end justify-between gap-3 pt-4">
                    <div className="min-w-0">
                        {discount > 0 && compareAt !== null && (
                            <p className="text-xs text-ink-3 line-through">
                                <span className="sr-only">Prix initial : </span>{money(compareAt)} {currency}
                            </p>
                        )}
                        <p className="whitespace-nowrap text-lg font-semibold text-ink">
                            {money(price)} <span className="text-sm font-medium text-ink-3">{currency}</span>
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onAdd}
                        disabled={adding}
                        aria-label={added ? `« ${title} » ajouté au panier` : `${addLabel} : ${title}`}
                        className={[
                            'inline-flex min-h-11 shrink-0 items-center justify-center gap-1.5 rounded-full px-4 text-sm font-semibold transition-colors disabled:opacity-50',
                            added ? 'bg-sage text-white' : 'bg-deep text-white hover:bg-accent-2',
                        ].join(' ')}
                    >
                        {added ? (
                            <>
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                    <path d="M5 12.5l4.5 4.5L19 7.5" />
                                </svg>
                                Ajouté
                            </>
                        ) : (
                            addLabel
                        )}
                    </button>
                </div>
                <span className="sr-only" aria-live="polite">{added ? 'Ajouté au panier' : ''}</span>
            </div>
        </article>
    );
}
