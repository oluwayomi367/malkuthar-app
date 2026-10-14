import { money } from '../../lib/format';

const SIZES = {
    sm: 'text-sm font-semibold',
    md: 'text-base font-semibold',
    lg: 'text-xl font-semibold',
    display: 'font-display text-[32px] font-semibold leading-none',
};

export default function Price({ price, compareAt = null, currency = 'FCFA', size = 'md', className = '' }) {
    const onSale = compareAt !== null && Number(compareAt) > Number(price);

    return (
        <span className={['inline-flex flex-wrap items-baseline gap-x-2.5 gap-y-1 text-ink', SIZES[size] ?? SIZES.md, className].filter(Boolean).join(' ')}>
            <span>
                {money(price)}
                <span className="ml-1 font-sans text-sm font-medium text-ink-3">{currency}</span>
            </span>
            {onSale && (
                <span className="font-sans text-sm font-normal text-ink-3 line-through">
                    <span className="sr-only">Prix initial : </span>{money(compareAt)} {currency}
                </span>
            )}
        </span>
    );
}
