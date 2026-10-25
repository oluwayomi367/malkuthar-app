import { Link } from '@inertiajs/react';
import Pagination from '../ui/Pagination';
import { getColorHex } from '../../lib/colors';

/* Briques visuelles partagées par les catalogues (boutique maison et place de marché).
   Aucune logique de filtre ici : chaque page garde ses paramètres d'URL. */

const CloseIcon = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" />
    </svg>
);

export function CatalogHeader({ title, intro, children }) {
    return (
        <header className="border-b border-line pb-6">
            <h1 className="font-display text-[36px] font-semibold leading-[1.1] text-ink sm:text-[44px]">{title}</h1>
            {intro && <p className="mt-2 max-w-2xl text-base text-ink-2">{intro}</p>}
            {children}
        </header>
    );
}

/** Rangée de segments (pilules) — défile horizontalement sur mobile. */
export function Segments({ label, items }) {
    return (
        <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul className="flex w-max gap-2 sm:w-auto sm:flex-wrap" aria-label={label}>
                {items.map((it) => (
                    <li key={it.key}>
                        <button type="button" onClick={it.onClick} aria-pressed={it.active}
                                className={['inline-flex min-h-11 items-center whitespace-nowrap rounded-full border px-4 text-sm font-medium transition-colors',
                                    it.active ? 'border-deep bg-deep text-white' : 'border-line-strong bg-surface text-ink hover:border-ink'].join(' ')}>
                            {it.label}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** Panneau de filtres : colonne sur desktop, tiroir sur mobile. */
export function FilterPanel({ open, onClose, onSubmit, hasActive, onReset, children }) {
    return (
        <>
            {open && (
                <button type="button" aria-label="Fermer les filtres" onClick={onClose}
                        className="fixed inset-0 z-40 bg-deep/40 lg:hidden" />
            )}
            <aside
                aria-label="Filtres"
                className={[
                    'fixed inset-y-0 left-0 z-50 w-[min(22rem,100%)] overflow-y-auto bg-surface px-5 pb-6 transition-transform duration-200',
                    'lg:static lg:z-auto lg:w-full lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0',
                    open ? 'visible translate-x-0' : 'invisible -translate-x-full lg:visible',
                ].join(' ')}
            >
                <div className="sticky top-0 -mx-5 mb-2 flex items-center justify-between border-b border-line bg-surface px-5 py-2 lg:hidden">
                    <p className="text-base font-semibold text-ink">Filtres</p>
                    <button type="button" onClick={onClose} aria-label="Fermer les filtres"
                            className="inline-flex h-11 w-11 items-center justify-center rounded-full text-ink-2 hover:bg-ground-alt hover:text-ink">
                        <CloseIcon />
                    </button>
                </div>

                <form onSubmit={onSubmit} className="divide-y divide-line">
                    {children}
                    <div className="space-y-2 pt-5">
                        <button type="submit"
                                className="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-deep px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2">
                            Appliquer les filtres
                        </button>
                        {hasActive && (
                            <button type="button" onClick={onReset}
                                    className="inline-flex min-h-11 w-full items-center justify-center rounded-full text-sm font-medium text-ink-2 underline decoration-line-strong underline-offset-4 hover:text-ink">
                                Réinitialiser les filtres
                            </button>
                        )}
                    </div>
                </form>
            </aside>
        </>
    );
}

export function FilterGroup({ title, children, defaultOpen = true }) {
    return (
        <details open={defaultOpen} className="group py-4 first:pt-0">
            <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-ink [&::-webkit-details-marker]:hidden">
                {title}
                <svg className="h-4 w-4 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </summary>
            <div className="mt-1">{children}</div>
        </details>
    );
}

export function SearchField({ value, onChange, onClear, placeholder = 'Rechercher' }) {
    return (
        <div className="pb-4">
            <label htmlFor="catalogue-recherche" className="text-sm font-semibold text-ink">Recherche</label>
            <div className="relative mt-2">
                <svg className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4.2-4.2" />
                </svg>
                <input id="catalogue-recherche" type="search" value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder}
                       className="h-11 w-full rounded-xl border border-line-strong bg-surface pl-10 pr-11 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />
                {value && (
                    <button type="button" onClick={onClear} aria-label="Effacer la recherche"
                            className="absolute right-0 top-0 inline-flex h-11 w-11 items-center justify-center text-ink-3 hover:text-ink">
                        <CloseIcon className="h-4 w-4" />
                    </button>
                )}
            </div>
        </div>
    );
}

export function PriceRange({ min, max, onMin, onMax, bounds, currency = 'FCFA' }) {
    return (
        <div className="grid grid-cols-2 gap-3">
            <label className="block text-xs font-medium text-ink-2">
                Minimum ({currency})
                <input type="number" min="0" inputMode="numeric" value={min} onChange={(e) => onMin(e.target.value)}
                       placeholder={String(bounds?.min ?? 0)}
                       className="mt-1 h-11 w-full rounded-xl border border-line-strong bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />
            </label>
            <label className="block text-xs font-medium text-ink-2">
                Maximum ({currency})
                <input type="number" min="0" inputMode="numeric" value={max} onChange={(e) => onMax(e.target.value)}
                       placeholder={String(bounds?.max ?? '')}
                       className="mt-1 h-11 w-full rounded-xl border border-line-strong bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />
            </label>
        </div>
    );
}

/** Option cochable (bouton) : case + libellé + compteur facultatif. */
export function CheckOption({ checked, onClick, label, count }) {
    return (
        <button type="button" onClick={onClick} aria-pressed={checked}
                className="group flex min-h-11 w-full items-center justify-between gap-3 text-left text-sm">
            <span className="flex min-w-0 items-center gap-2.5">
                <span className={['flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded border transition-colors',
                    checked ? 'border-deep bg-deep text-white' : 'border-line-strong bg-surface group-hover:border-ink'].join(' ')}>
                    {checked && (
                        <svg className="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M2.5 6l2.5 2.5 4.5-5" />
                        </svg>
                    )}
                </span>
                <span className={['truncate', checked ? 'font-semibold text-ink' : 'text-ink-2 group-hover:text-ink'].join(' ')}>{label}</span>
            </span>
            {count !== undefined && count !== null && <span className="shrink-0 text-xs text-ink-3">{count}</span>}
        </button>
    );
}

export function ColorOptions({ colors, selected, onToggle }) {
    return (
        <div className="grid grid-cols-2 gap-2">
            {colors.map((name) => {
                const on = selected === name;
                return (
                    <button key={name} type="button" onClick={() => onToggle(name)} aria-pressed={on}
                            className={['flex min-h-11 items-center gap-2 rounded-xl border px-2.5 text-left text-sm transition-colors',
                                on ? 'border-ink bg-ground-alt font-semibold text-ink' : 'border-line bg-surface text-ink-2 hover:border-line-strong hover:text-ink'].join(' ')}>
                        <span className="h-4 w-4 shrink-0 rounded-full border border-ink/15" style={{ backgroundColor: getColorHex(name) }} aria-hidden="true" />
                        <span className="truncate">{name}</span>
                    </button>
                );
            })}
        </div>
    );
}

/** Barre au-dessus de la grille : nombre de résultats, tri, bouton Filtres (mobile). */
export function CatalogToolbar({ countText, sortId, sortValue, onSort, options, onOpenFilters, activeCount = 0 }) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-ink-2" aria-live="polite">{countText}</p>
            <div className="flex items-center gap-2">
                <button type="button" onClick={onOpenFilters}
                        className="inline-flex min-h-11 items-center gap-2 rounded-full border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-ink lg:hidden">
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true">
                        <path d="M4 7h16M7 12h10M10 17h4" />
                    </svg>
                    Filtres{activeCount > 0 && ` (${activeCount})`}
                </button>
                <label htmlFor={sortId} className="sr-only sm:not-sr-only sm:text-sm sm:text-ink-2">Trier par</label>
                <select id={sortId} value={sortValue} onChange={(e) => onSort(e.target.value)}
                        className="h-11 rounded-full border border-line-strong bg-surface pl-4 pr-9 text-sm font-medium text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    {options.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                </select>
            </div>
        </div>
    );
}

export function ActiveFilters({ chips, onClear }) {
    if (!chips.length) return null;

    return (
        <div className="flex flex-wrap items-center gap-2">
            {chips.map((chip, idx) => (
                <span key={idx} className="inline-flex items-center gap-1 rounded-full border border-line bg-ground-alt pl-3 text-sm text-ink">
                    {chip.label}
                    <button type="button" onClick={chip.remove} aria-label={`Retirer le filtre ${chip.label}`}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-full text-ink-3 hover:text-ink">
                        <CloseIcon className="h-4 w-4" />
                    </button>
                </span>
            ))}
            <button type="button" onClick={onClear}
                    className="inline-flex min-h-11 items-center px-2 text-sm font-medium text-ink-2 underline decoration-line-strong underline-offset-4 hover:text-ink">
                Tout effacer
            </button>
        </div>
    );
}

export function LoadMore({ paginator, endText }) {
    return (
        <div className="mt-12 flex flex-col items-center gap-5">
            {paginator.next_page_url ? (
                <Link href={paginator.next_page_url} preserveScroll
                      className="inline-flex min-h-11 items-center justify-center rounded-full border border-line-strong bg-surface px-8 text-sm font-semibold text-ink transition-colors hover:border-ink">
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
