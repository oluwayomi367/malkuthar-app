import { useState } from 'react';
import RosetteBadge from './RosetteBadge';

export default function ProductGallery({ product, className = '' }) {
    const images = [product.main_image, ...(product.gallery ?? [])].filter(Boolean)
        .filter((v, i, a) => a.indexOf(v) === i);
    const [current, setCurrent] = useState(images.length ? 0 : null);

    const hasSale = product.promotional_price !== null && product.promotional_price !== undefined && Number(product.promotional_price) < Number(product.price);
    const discountPercent = hasSale ? Math.round(100 - (product.promotional_price / product.price) * 100) : 0;

    return (
        <div className={className}>
            <div className="relative overflow-hidden rounded-2xl border border-line bg-surface">
                <div className="flex aspect-square items-center justify-center">
                    {images.length ? (
                        images.map((img, i) => (
                            <img key={img} src={img} alt={i === 0 ? product.name : `${product.name} — vue ${i + 1}`} hidden={current !== i}
                                 className="h-full w-full object-contain" />
                        ))
                    ) : (
                        <div className="flex h-full w-full items-center justify-center bg-ground-alt text-ink-3">
                            <svg className="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.3" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 15l5-5 4 4 3-3 6 6" /><circle cx="9" cy="9" r="1.5" />
                            </svg>
                        </div>
                    )}
                </div>
                {hasSale && (
                    <span className="absolute left-3 top-3">
                        <RosetteBadge discount={discountPercent} />
                    </span>
                )}
            </div>

            {images.length > 1 && (
                <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
                    {images.map((img, i) => (
                        <button key={img} type="button" onClick={() => setCurrent(i)}
                                aria-label={`Afficher la vue ${i + 1}`} aria-pressed={current === i}
                                className={['h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-surface transition-colors',
                                    current === i ? 'border-ink' : 'border-line hover:border-line-strong'].join(' ')}>
                            <img src={img} alt="" className="h-full w-full object-cover" />
                        </button>
                    ))}
                </div>
            )}

            {product.presentation_video && (
                <div className="mt-3 overflow-hidden rounded-2xl border border-line">
                    <video src={product.presentation_video} controls preload="none" className="aspect-video w-full bg-deep" />
                </div>
            )}
        </div>
    );
}
