export default function StockIndicator({ available, lowThreshold = 5, className = '' }) {
    if (available === null || available === undefined) return null;

    const n = Number(available);
    const base = 'inline-flex items-center gap-2 text-sm';

    if (n <= 0) {
        return (
            <p className={[base, 'font-medium text-rust', className].filter(Boolean).join(' ')}>
                <span className="h-2 w-2 rounded-full bg-current" aria-hidden="true" />Épuisé
            </p>
        );
    }

    if (n <= lowThreshold) {
        return (
            <p className={[base, 'font-medium text-amber', className].filter(Boolean).join(' ')}>
                <span className="h-2 w-2 rounded-full bg-current" aria-hidden="true" />Plus que {n} en stock
            </p>
        );
    }

    return (
        <p className={[base, 'text-ink-2', className].filter(Boolean).join(' ')}>
            <span className="h-2 w-2 rounded-full bg-sage" aria-hidden="true" />En stock
        </p>
    );
}
