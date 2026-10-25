import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import Pagination from '../ui/Pagination';
import { getColorHex } from '../../lib/colors';

/* Briques visuelles de la boutique maison (style vitrine : blanc, noir, gris).
   Aucune logique de filtre ici : la page garde ses paramètres d'URL. */

const CloseIcon = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" />
    </svg>
);

const SearchIcon = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true">
        <circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4.2-4.2" />
    </svg>
);

const FilterIcon = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true">
        <path d="M21 6H3M10 12H3M10 18H3" /><circle cx="17" cy="15" r="3" /><path d="M21 19l-1.9-1.9" />
    </svg>
);

export function ShopBreadcrumb({ items }) {
    return (
        <nav aria-label="Fil d'Ariane" className="w-full overflow-hidden pb-2 pt-4 text-sm">
            <ol className="flex items-center gap-1">
                {items.map((c, i) => (
                    <li key={`${c.label}-${i}`} className="flex items-center truncate whitespace-nowrap">
                        {i > 0 && <span aria-hidden="true" className="mx-0.5 lg:mx-1">/</span>}
                        {c.href ? (
                            <Link href={c.href} className="hover:underline">{c.label}</Link>
                        ) : (
                            <span aria-current="page" className="truncate font-semibold">{c.label}</span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

/** Bandeau de catégorie : titre + texte à gauche, visuel assombri à droite. */
export function ShopBanner({ title, intro, image }) {
    return (
        <header className="mb-4 mt-4 gap-8 rounded-lg lg:mt-0 lg:grid lg:grid-cols-2 lg:border-2 lg:border-line lg:bg-ground lg:p-10">
            <div className="flex flex-col justify-center">
                <h1 className="mb-4 text-3xl font-bold text-ink lg:text-5xl">{title}</h1>
                {intro && <p className="text-ink-2">{intro}</p>}
            </div>
            {image && (
                <div className="relative hidden lg:block">
                    <img src={image} alt="" className="h-64 w-full overflow-hidden rounded-lg object-cover" />
                    <div className="absolute inset-0 flex items-center justify-center rounded-lg bg-ink/50">
                        <span className="text-2xl font-bold uppercase tracking-[0.3em] text-white">Malkuthar</span>
                    </div>
                </div>
            )}
        </header>
    );
}

/** Menu « Trier par » : bouton gris + liste déroulante. */
export function SortMenu({ value, onChange, options }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const current = options.find(([v]) => v === value)?.[1] ?? options[0][1];

    useEffect(() => {
        if (!open) return undefined;
        const close = (e) => { if (!ref.current?.contains(e.target)) setOpen(false); };
        const esc = (e) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', esc);
        return () => { document.removeEventListener('mousedown', close); document.removeEventListener('keydown', esc); };
    }, [open]);

    return (
        <div className="py-2 lg:flex lg:items-center lg:justify-end">
            <span id="tri-libelle" className="font-bold lg:mr-2 lg:font-medium">Trier par :</span>
            <div ref={ref} className="relative mt-2 lg:mt-0">
                <button type="button" onClick={() => setOpen((o) => !o)} aria-haspopup="listbox" aria-expanded={open} aria-labelledby="tri-libelle"
                        className="flex h-12 w-full items-center justify-between gap-1 rounded border border-line-strong bg-ground px-3 text-ink lg:w-52">
                    {current}
                    <svg className={['h-4 w-4 transition-transform', open ? 'rotate-180' : ''].join(' ')} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </button>
                {open && (
                    <ul role="listbox" aria-labelledby="tri-libelle"
                        className="absolute right-0 z-30 mt-1 w-full overflow-hidden rounded-md border border-line bg-surface py-1 shadow-lg lg:w-52">
                        {options.map(([v, label]) => (
                            <li key={v} role="option" aria-selected={v === value}>
                                <button type="button" onClick={() => { setOpen(false); onChange(v); }}
                                        className="flex min-h-11 w-full items-center justify-between px-3 text-left text-sm text-ink hover:bg-ground-alt">
                                    {label}
                                    {v === value && <span className="ml-2 text-ink-2" aria-hidden="true">✓</span>}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}

/** Panneau de filtres : colonne sur desktop, tiroir sur mobile (ouvert par un bouton flottant). */
export function ShopFilterPanel({ open, onOpen, onClose, onSubmit, hasActive, onReset, activeCount = 0, children }) {
    return (
        <>
            {open && (
                <button type="button" aria-label="Fermer les filtres" onClick={onClose}
                        className="fixed inset-0 z-40 bg-ink/50 lg:hidden" />
            )}
            <aside
                aria-label="Filtres"
                className={[
                    'fixed inset-y-0 left-0 z-50 w-[min(24rem,100%)] overflow-y-auto bg-surface transition-transform duration-200',
                    'lg:static lg:z-auto lg:w-full lg:translate-x-0 lg:overflow-visible lg:rounded-lg lg:pr-8',
                    open ? 'visible translate-x-0' : 'invisible -translate-x-full lg:visible',
                ].join(' ')}
            >
                <div className="mb-4 flex items-center justify-between bg-deep p-4 font-bold text-white lg:rounded lg:bg-ground-alt lg:p-3 lg:text-ink">
                    <span className="flex items-center gap-2"><FilterIcon /> OPTIONS DE RECHERCHE</span>
                    <button type="button" onClick={onClose} aria-label="Fermer les filtres"
                            className="-m-2 inline-flex h-11 w-11 items-center justify-center rounded-full hover:bg-white/10 lg:hidden">
                        <CloseIcon />
                    </button>
                </div>

                <form onSubmit={onSubmit} className="px-5 pb-10 lg:px-0">
                    {children}
                    <div className="space-y-2">
                        <button type="submit"
                                className="inline-flex h-14 w-full items-center justify-center rounded-md bg-deep text-white transition-colors hover:bg-deep-2">
                            Filtrer
                        </button>
                        {hasActive && (
                            <button type="button" onClick={onReset}
                                    className="inline-flex min-h-11 w-full items-center justify-center text-sm text-ink-2 underline underline-offset-4 hover:text-ink">
                                Réinitialiser les filtres
                            </button>
                        )}
                    </div>
                </form>
            </aside>

            <div className="fixed bottom-5 left-5 z-30 overflow-hidden rounded-full bg-deep max-lg:[body:has([data-sticky-cta])_&]:bottom-24 lg:hidden">
                <button type="button" onClick={onOpen} aria-label={`Ouvrir les filtres${activeCount > 0 ? ` (${activeCount} actifs)` : ''}`}
                        className="relative p-4 text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-deep-2">
                    <FilterIcon className="h-7 w-7" />
                    {activeCount > 0 && (
                        <span className="absolute right-2 top-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-surface px-1 text-[11px] font-bold text-ink">{activeCount}</span>
                    )}
                </button>
            </div>
        </>
    );
}

/** Groupe repliable : titre en capitales, +/−, pastille du nombre de choix actifs. */
export function ShopFilterGroup({ title, badge = 0, defaultOpen = true, children }) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <div className="mb-4 border-b border-line pb-4">
            <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open}
                    className={['flex min-h-11 w-full cursor-pointer items-center justify-between px-2 py-3 text-left font-semibold uppercase tracking-wide text-ink hover:bg-ground-alt',
                        badge > 0 ? 'bg-ground-alt' : ''].join(' ')}>
                <span>
                    {title}
                    {badge > 0 && <span className="ml-2 rounded-full bg-deep px-2 py-1 text-xs text-white">{badge}</span>}
                </span>
                <svg className="h-6 w-6 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true">
                    {open ? <path d="M5 12h14" /> : <path d="M12 5v14M5 12h14" />}
                </svg>
            </button>
            {open && <div className="mt-2 space-y-1 px-2">{children}</div>}
        </div>
    );
}

export function ShopSearchField({ value, onChange, onClear, placeholder = 'Rechercher un produit...' }) {
    return (
        <div className="relative mb-4">
            <label htmlFor="boutique-recherche" className="sr-only">Rechercher</label>
            <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink" />
            <input id="boutique-recherche" type="search" value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder}
                   className="h-12 w-full rounded-md border border-line-strong bg-surface pl-10 pr-11 text-sm text-ink placeholder:text-ink-3 focus:border-deep focus:outline-none" />
            {value && (
                <button type="button" onClick={onClear} aria-label="Effacer la recherche"
                        className="absolute right-0 top-0 inline-flex h-12 w-11 items-center justify-center text-ink-3 hover:text-ink">
                    <CloseIcon className="h-4 w-4" />
                </button>
            )}
        </div>
    );
}

export function ShopPriceRange({ min, max, onMin, onMax, bounds, currency = 'FCFA' }) {
    const input = 'mt-1 h-11 w-full rounded-md border border-line-strong bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-deep focus:outline-none';

    return (
        <div className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
                <label className="block text-xs font-medium text-ink-2">
                    Minimum
                    <input type="number" min="0" inputMode="numeric" value={min} onChange={(e) => onMin(e.target.value)}
                           placeholder={String(bounds?.min ?? 0)} className={input} />
                </label>
                <label className="block text-xs font-medium text-ink-2">
                    Maximum
                    <input type="number" min="0" inputMode="numeric" value={max} onChange={(e) => onMax(e.target.value)}
                           placeholder={String(bounds?.max ?? '')} className={input} />
                </label>
            </div>
            <div className="flex justify-between text-sm font-medium text-ink">
                <span>{Number(min || bounds?.min || 0).toLocaleString('fr-FR')} {currency}</span>
                <span>{Number(max || bounds?.max || 0).toLocaleString('fr-FR')} {currency}</span>
            </div>
        </div>
    );
}

/** Case à cocher : libellé en gras quand actif, compteur entre crochets, pastille de couleur facultative. */
export function ShopCheckOption({ checked, onClick, label, count, color }) {
    return (
        <button type="button" onClick={onClick} aria-pressed={checked}
                className="group flex min-h-10 w-full items-center justify-between gap-3 py-1 text-left">
            <span className="flex min-w-0 items-center gap-3">
                <span className={['flex h-5 w-5 shrink-0 items-center justify-center rounded-[4px] border transition-colors',
                    checked ? 'border-deep bg-deep text-white' : 'border-line-strong bg-surface group-hover:border-deep'].join(' ')}>
                    {checked && (
                        <svg className="h-3.5 w-3.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M2.5 6l2.5 2.5 4.5-5" />
                        </svg>
                    )}
                </span>
                <span className={['flex min-w-0 items-center', checked ? 'font-bold text-ink' : 'text-ink'].join(' ')}>
                    {color && <span className="mr-2 h-4 w-4 shrink-0 rounded-full border-2 border-line opacity-80" style={{ backgroundColor: color }} aria-hidden="true" />}
                    <span className="truncate">{label}</span>
                </span>
            </span>
            {count !== undefined && count !== null && <span className="shrink-0 text-sm text-ink-2">[{count}]</span>}
        </button>
    );
}

export function ShopColorOptions({ colors, selected, onToggle }) {
    return colors.map((name) => (
        <ShopCheckOption key={name} checked={selected === name} label={name} color={getColorHex(name)} onClick={() => onToggle(name)} />
    ));
}

export function ShopActiveFilters({ chips, onClear }) {
    if (!chips.length) return null;

    return (
        <div className="flex flex-wrap items-center gap-2">
            {chips.map((chip, idx) => (
                <span key={idx} className="inline-flex items-center gap-1 rounded-full bg-ground-alt pl-3 text-sm text-ink">
                    {chip.label}
                    <button type="button" onClick={chip.remove} aria-label={`Retirer le filtre ${chip.label}`}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-full text-ink-3 hover:text-ink">
                        <CloseIcon className="h-4 w-4" />
                    </button>
                </span>
            ))}
            <button type="button" onClick={onClear} className="inline-flex min-h-11 items-center px-2 text-sm font-medium text-ink-2 underline underline-offset-4 hover:text-ink">
                Tout effacer
            </button>
        </div>
    );
}

export function ShopLoadMore({ paginator, endText }) {
    return (
        <div className="mb-12 mt-8 flex flex-col items-center gap-3 text-center">
            {paginator.next_page_url ? (
                <Link href={paginator.next_page_url} preserveScroll
                      className="inline-flex h-14 w-full items-center justify-center rounded-[4px] bg-deep text-lg text-white transition-all hover:bg-deep-2 lg:w-auto lg:px-20">
                    Voir plus
                </Link>
            ) : (
                <p className="text-sm text-ink-3">{endText}</p>
            )}
            <div className="w-full">
                <Pagination paginator={paginator} />
            </div>
        </div>
    );
}
