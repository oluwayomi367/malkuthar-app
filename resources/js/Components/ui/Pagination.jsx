import { Link } from '@inertiajs/react';

function Chevron({ dir }) {
    return (
        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
            <path d={dir === 'prev' ? 'M10 3L5 8l5 5' : 'M6 3l5 5-5 5'} strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

const BASE = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border px-4 text-sm font-medium';
const ACTIVE = `${BASE} border-line-strong bg-surface text-ink transition-colors hover:border-ink`;
const INACTIVE = `${BASE} border-line text-ink-3`;

/** `paginator` = objet Laravel paginé sérialisé (current_page, last_page, prev/next_page_url…). */
export default function Pagination({ paginator, className = '' }) {
    if (!paginator || paginator.last_page <= 1) return null;

    return (
        <nav aria-label="Pagination" className={['mt-10 flex items-center justify-between gap-3 border-t border-line pt-6 text-sm', className].filter(Boolean).join(' ')}>
            {paginator.prev_page_url
                ? <Link href={paginator.prev_page_url} className={ACTIVE}><Chevron dir="prev" />Précédent</Link>
                : <span className={INACTIVE} aria-disabled="true"><Chevron dir="prev" />Précédent</span>}

            <span className="text-ink-2">Page {paginator.current_page} sur {paginator.last_page}</span>

            {paginator.next_page_url
                ? <Link href={paginator.next_page_url} className={ACTIVE}>Suivant<Chevron dir="next" /></Link>
                : <span className={INACTIVE} aria-disabled="true">Suivant<Chevron dir="next" /></span>}
        </nav>
    );
}
