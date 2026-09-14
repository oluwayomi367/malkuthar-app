export default function StockIndicator({ available, lowThreshold = 5, className = '' }) {
    if (available === null || available === undefined) return null;

    const n = Number(available);

    if (n <= 0) {
        return (
            <p className={['inline-flex items-center gap-2 text-sm font-medium text-rust', className].filter(Boolean).join(' ')}>
                <span className="h-1.5 w-1.5 rounded-full bg-current" />Épuisé
            </p>
        );
    }

    if (n <= lowThreshold) {
        return (
            <p className={['inline-flex items-center gap-2 text-sm font-medium text-rust', className].filter(Boolean).join(' ')}>
                <span className="h-1.5 w-1.5 rounded-full bg-current" />Plus que {n} en stock
            </p>
        );
    }

    return (
        <p className={['inline-flex items-center gap-2 text-sm text-ink-3', className].filter(Boolean).join(' ')}>
            <span className="h-1.5 w-1.5 rounded-full bg-forest" />En stock
        </p>
    );
}
