import { Link } from '@inertiajs/react';

/** `paginator` = objet Laravel paginé sérialisé (current_page, last_page, prev/next_page_url…). */
export default function Pagination({ paginator, className = '' }) {
    if (!paginator || paginator.last_page <= 1) return null;

    return (
        <nav aria-label="Pagination" className={['mt-8 flex items-center justify-between border-t border-line pt-4 text-sm', className].filter(Boolean).join(' ')}>
            {paginator.prev_page_url
                ? <Link href={paginator.prev_page_url} className="text-ink transition-colors hover:text-accent">‹ Précédent</Link>
                : <span className="text-ink-3">‹ Précédent</span>}

            <span className="text-ink-3">Page {paginator.current_page} / {paginator.last_page}</span>

            {paginator.next_page_url
                ? <Link href={paginator.next_page_url} className="text-ink transition-colors hover:text-accent">Suivant ›</Link>
                : <span className="text-ink-3">Suivant ›</span>}
        </nav>
    );
}
