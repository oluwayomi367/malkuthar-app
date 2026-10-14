import { Link } from '@inertiajs/react';

/**
 * Gabarit commun des fiches produit (boutique maison et place de marché) :
 * galerie à gauche, bloc d'achat collant à droite (desktop), contenu long dessous.
 */
export default function ProductLayout({ breadcrumb, gallery, children, below }) {
    return (
        <>
            <div className="mx-auto max-w-[1280px] px-4 pt-6 sm:px-6 lg:pt-10">
                {breadcrumb}
                <div className="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-14">
                    <div className="min-w-0">{gallery}</div>
                    <div className="min-w-0 lg:sticky lg:top-24 lg:self-start">{children}</div>
                </div>
            </div>
            {below && (
                <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-12 sm:px-6">{below}</div>
            )}
        </>
    );
}

/** En-tête du bloc d'achat : vendeur + type, titre h1, ligne d'informations. */
export function ProductHeader({ title, seller, typeLabel, children }) {
    return (
        <header>
            <div className="flex flex-wrap items-center gap-2">
                {seller?.href ? (
                    <Link href={seller.href}
                          className={['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                              seller.house ? 'border-accent/25 bg-accent-soft text-accent-2' : 'border-line-strong bg-ground-alt text-ink hover:border-ink'].join(' ')}>
                        {seller.label}
                    </Link>
                ) : seller?.label ? (
                    <span className={['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                        seller.house ? 'border-accent/25 bg-accent-soft text-accent-2' : 'border-line-strong bg-ground-alt text-ink'].join(' ')}>
                        {seller.label}
                    </span>
                ) : null}
                {typeLabel && (
                    <span className="inline-flex items-center rounded-full border border-line px-2.5 py-0.5 text-xs font-medium text-ink-2">{typeLabel}</span>
                )}
            </div>
            <h1 className="mt-3 font-display text-[32px] font-semibold leading-[1.1] text-ink sm:text-[40px]">{title}</h1>
            {children && <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-2">{children}</div>}
        </header>
    );
}

/** Carte du bloc d'achat. */
export function BuyBox({ children, className = '' }) {
    return (
        <div className={['mt-6 rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-6', className].filter(Boolean).join(' ')}>
            {children}
        </div>
    );
}

/** Sélecteur d'option (variante) : boutons ≥ 44px, état sélectionné = couleur + aria-pressed. */
export function OptionGroup({ label, values, selected, onSelect }) {
    return (
        <fieldset>
            <legend className="flex w-full items-baseline justify-between gap-3 text-sm font-semibold text-ink">
                <span>{label}</span>
                {selected && <span className="font-normal text-ink-2">{selected}</span>}
            </legend>
            <div className="mt-2 flex flex-wrap gap-2">
                {values.map((val) => {
                    const on = selected === val;
                    return (
                        <button key={val} type="button" onClick={() => onSelect(val)} aria-pressed={on}
                                className={['inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border px-3.5 text-sm font-medium transition-colors',
                                    on ? 'border-ink bg-ink text-white' : 'border-line-strong bg-surface text-ink hover:border-ink'].join(' ')}>
                            {val}
                        </button>
                    );
                })}
            </div>
        </fieldset>
    );
}

/** Sélecteur de quantité. */
export function QuantityInput({ value, onDec, onInc, decDisabled = false, incDisabled = false, disabled = false }) {
    return (
        <div className="inline-flex h-11 items-center rounded-full border border-line-strong bg-surface">
            <button type="button" onClick={onDec} disabled={disabled || decDisabled} aria-label="Diminuer la quantité"
                    className="flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt disabled:opacity-40">
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
            </button>
            <span className="w-8 text-center text-base font-semibold text-ink" aria-live="polite">
                <span className="sr-only">Quantité : </span>{value}
            </span>
            <button type="button" onClick={onInc} disabled={disabled || incDisabled} aria-label="Augmenter la quantité"
                    className="flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt disabled:opacity-40">
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            </button>
        </div>
    );
}
