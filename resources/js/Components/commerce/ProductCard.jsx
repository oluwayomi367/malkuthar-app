import { Link } from '@inertiajs/react';
import Badge from '../ui/Badge';
import Price from './Price';

const TYPE_LABELS = { digital: 'Digital', physical: 'Physique', service: 'Service' };

export default function ProductCard({ product }) {
    const isAffiliable = product.type === 'digital' && product.is_affiliable;
    const price = product.promotional_price ?? product.price;

    return (
        <Link href={route('catalog.show', product.slug)} className="group flex flex-col">
            <div className="relative overflow-hidden rounded-2xl border border-black/10 bg-surface">
                <div className="aspect-[4/5]">
                    {product.main_image ? (
                        <img src={product.main_image} alt={product.name} loading="lazy"
                             className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-black/[0.04] to-transparent text-ink-3">
                            <svg className="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.1">
                                <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 15l5-5 4 4 3-3 6 6" /><circle cx="9" cy="9" r="1.5" />
                            </svg>
                        </div>
                    )}
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
                </div>

                <span className="absolute left-2.5 top-2.5">
                    <Badge tone="ink">{TYPE_LABELS[product.type] ?? product.type}</Badge>
                </span>
                {isAffiliable && (
                    <span className="absolute right-2.5 top-2.5">
                        <Badge tone="gold">
                            <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="currentColor"><path d="M6 0l1.6 3.4L11 4l-2.6 2.3.7 3.7L6 8.2 2.9 10l.7-3.7L1 4l3.4-.6z" /></svg>
                            Affiliable
                        </Badge>
                    </span>
                )}
            </div>

            <p className="mt-2.5 line-clamp-2 text-[15px] font-medium leading-snug text-ink transition-colors group-hover:text-accent">{product.name}</p>
            {product.category && <p className="mt-0.5 text-xs text-ink-3">{product.category}</p>}
            <div className="mt-1">
                <Price price={price} compareAt={product.promotional_price ? product.price : null} currency={product.currency} />
            </div>
        </Link>
    );
}
