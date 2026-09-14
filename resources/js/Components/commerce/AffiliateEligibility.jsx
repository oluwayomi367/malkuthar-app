import { usePage } from '@inertiajs/react';

export default function AffiliateEligibility({ product, className = '' }) {
    const { commissionRates } = usePage().props;

    if (product.type !== 'digital' || !product.is_affiliable) return null;

    const rates = commissionRates ?? { 1: 15, 2: 10, 3: 5 };

    return (
        <div className={['rounded-lg border border-gold/35 bg-gold-tint p-4', className].filter(Boolean).join(' ')}>
            <div className="flex items-start gap-2.5">
                <svg className="mt-0.5 h-4 w-4 shrink-0 text-gold" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M11 3a2 2 0 1 0-1.9 2.7L6.3 8A2 2 0 1 0 6.3 10l2.8 2.3A2 2 0 1 0 11 10l-3-2.4 3-2.4A2 2 0 0 0 11 3z" />
                </svg>
                <div>
                    <p className="text-[13px] font-semibold text-ink">Ce produit est affiliable</p>
                    <p className="mt-0.5 text-[13px] text-ink-2">
                        Partagez-le et gagnez <strong className="text-ink">{rates[1]} %</strong> ·{' '}
                        <strong className="text-ink">{rates[2]} %</strong> ·{' '}
                        <strong className="text-ink">{rates[3]} %</strong> sur trois niveaux.{' '}
                        <a href={route('affiliate.program-terms')} className="whitespace-nowrap underline decoration-gold underline-offset-2">Conditions →</a>
                    </p>
                </div>
            </div>
        </div>
    );
}
