import { Link } from '@inertiajs/react';

export function Breadcrumb({ items = null, product = null }) {
    const crumbs = items ?? [
        { label: 'Boutique', href: route('catalog.index') },
        ...(product?.category ? [{ label: product.category, href: route('catalog.index', { category: product.category }) }] : []),
    ];

    return (
        <nav aria-label="Fil d'Ariane" className="text-sm text-ink-3">
            <ol className="flex flex-wrap items-center gap-x-2 gap-y-1">
                {crumbs.map((c, i) => (
                    <li key={`${c.label}-${i}`} className="flex min-w-0 items-center gap-2">
                        {i > 0 && <span aria-hidden="true" className="text-line-strong">/</span>}
                        {c.href ? (
                            <Link href={c.href} className="py-1 hover:text-ink hover:underline hover:underline-offset-4">{c.label}</Link>
                        ) : (
                            <span className="line-clamp-1 max-w-[16rem] text-ink-2" aria-current="page">{c.label}</span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

/** `accordions` : [[titre, html], ...] — html injecté tel quel (contenu statique côté serveur). */
export default function ProductDescription({ product, accordions = [] }) {
    return (
        <section className="border-t border-line pt-10" aria-labelledby="description-produit">
            <h2 id="description-produit" className="font-display text-[28px] font-semibold leading-tight text-ink">Description</h2>
            <div className="mt-4 max-w-prose whitespace-pre-line text-base leading-[1.7] text-ink-2">{product.description}</div>

            {accordions.length > 0 && (
                <div className="mt-8 max-w-prose divide-y divide-line border-y border-line">
                    {accordions.map(([title, body]) => (
                        <details key={title} className="group">
                            <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 py-3 text-base font-semibold text-ink [&::-webkit-details-marker]:hidden">
                                {title}
                                <svg className="h-5 w-5 shrink-0 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </summary>
                            <p className="pb-4 text-base leading-[1.7] text-ink-2" dangerouslySetInnerHTML={{ __html: body }} />
                        </details>
                    ))}
                </div>
            )}
        </section>
    );
}
