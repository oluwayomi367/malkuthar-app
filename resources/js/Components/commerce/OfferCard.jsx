import { Link } from '@inertiajs/react';
import { money } from '../../lib/format';

const TYPE_LABELS = { physical: 'Produit', digital: 'Digital', service: 'Service', training: 'Formation' };

/** `offer` : forme pré-sérialisée par le contrôleur (voir MarketplaceShopController)
 *  — `main_image_url` déjà résolu (Storage::url côté serveur). */
export default function OfferCard({ offer }) {
    const hasSale = offer.sale_price !== null && Number(offer.sale_price) < Number(offer.price);
    const off = hasSale ? Math.round(100 - (offer.sale_price / offer.price) * 100) : 0;

    return (
        <Link href={route('marketplace.shop.offer', offer.slug)}
              className="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-[0_16px_40px_-24px_rgba(46,44,40,0.32)]">
            <div className="relative aspect-[4/3] overflow-hidden bg-ground-alt">
                {offer.main_image_url ? (
                    <img src={offer.main_image_url} alt={offer.title} loading="lazy"
                         className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" />
                ) : (
                    <div className="flex h-full items-center justify-center text-line-strong">
                        <svg className="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" /></svg>
                    </div>
                )}
                <span className="absolute left-2 top-2 rounded-full bg-ground/90 px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-wide text-ink-2 backdrop-blur">
                    {TYPE_LABELS[offer.type] ?? offer.type}
                </span>
                {off > 0 && <span className="absolute right-2 top-2 rounded-full bg-rust px-2 py-0.5 text-[11px] font-bold text-white">−{off}%</span>}
            </div>
            <div className="flex flex-1 flex-col p-3.5">
                <p className="truncate text-[11px] text-ink-3">{offer.vendor?.display_name}</p>
                <p className="mt-0.5 line-clamp-2 font-display text-[15px] font-semibold leading-snug text-ink">{offer.title}</p>

                {offer.rating_count > 0 && (
                    <p className="mt-1 flex items-center gap-1 text-[11px] text-ink-3">
                        <span className="text-accent">
                            {'★'.repeat(Math.round(offer.rating_avg))}
                            <span className="text-line-strong">{'★'.repeat(5 - Math.round(offer.rating_avg))}</span>
                        </span>
                        <span>{offer.rating_avg.toFixed(1).replace('.', ',')} · {offer.rating_count}</span>
                    </p>
                )}

                <div className="mt-auto flex items-baseline gap-2 pt-3">
                    <span className="font-display font-semibold text-ink">{money(offer.effective_price)} {offer.currency}</span>
                    {hasSale && <span className="text-xs text-ink-3 line-through">{money(offer.price)}</span>}
                </div>
            </div>
        </Link>
    );
}
