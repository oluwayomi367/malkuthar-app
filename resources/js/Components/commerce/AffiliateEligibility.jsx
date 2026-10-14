import { usePage } from '@inertiajs/react';

export default function AffiliateEligibility({ product, className = '' }) {
    const { commissionRates } = usePage().props;

    if (product.type !== 'digital' || !product.is_affiliable) return null;

    const rates = commissionRates ?? { 1: 15, 2: 10, 3: 5 };

    return (
        <div className={['rounded-xl border border-accent/25 bg-accent-soft px-4 py-3.5', className].filter(Boolean).join(' ')}>
            <p className="text-sm font-semibold text-ink">Ce produit est affiliable</p>
            <p className="mt-1 text-sm leading-relaxed text-ink-2">
                En le recommandant avec votre lien d'affilié, vous touchez une commission sur chaque vente :
                {' '}<strong className="font-semibold text-ink">{rates[1]} %</strong> au niveau 1,
                {' '}<strong className="font-semibold text-ink">{rates[2]} %</strong> au niveau 2,
                {' '}<strong className="font-semibold text-ink">{rates[3]} %</strong> au niveau 3.
            </p>
            <a href={route('affiliate.program-terms')}
               className="mt-1.5 inline-flex min-h-11 items-center text-sm font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent-2">
                Conditions du programme
            </a>
        </div>
    );
}
