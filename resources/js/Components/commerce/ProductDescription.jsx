import { Link } from '@inertiajs/react';

export function Breadcrumb({ product }) {
    return (
        <nav className="flex items-center gap-1.5 text-sm text-ink-3">
            <Link href={route('catalog.index')} className="hover:text-ink">Boutique</Link>
            {product.category && (
                <>
                    <span>/</span>
                    <Link href={route('catalog.index', { category: product.category })} className="hover:text-ink">{product.category}</Link>
                </>
            )}
        </nav>
    );
}

/** `accordions` : [[titre, html], ...] — html injecté tel quel (contenu statique côté serveur). */
export default function ProductDescription({ product, accordions = [] }) {
    return (
        <section className="border-t border-line pt-8">
            <h2 className="text-lg font-bold text-ink">Description</h2>
            <div className="mt-3 max-w-2xl whitespace-pre-line text-[15px] leading-relaxed text-ink-2">{product.description}</div>

            {accordions.length > 0 && (
                <div className="mt-6 max-w-2xl divide-y divide-line border-y border-line">
                    {accordions.map(([title, body]) => (
                        <details key={title} className="group py-3">
                            <summary className="flex cursor-pointer list-none items-center justify-between text-[15px] font-medium text-ink">
                                {title}
                                <svg className="h-4 w-4 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
                                    <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </summary>
                            <p className="mt-2 text-sm text-ink-2" dangerouslySetInnerHTML={{ __html: body }} />
                        </details>
                    ))}
                </div>
            )}
        </section>
    );
}
