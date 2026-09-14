import { money } from '../../lib/format';

const SIZES = { sm: 'text-sm', md: 'text-[15px]', lg: 'text-xl' };

export default function Price({ price, compareAt = null, currency = 'FCFA', size = 'md', className = '' }) {
    const onSale = compareAt !== null && Number(compareAt) > Number(price);

    return (
        <span className={['inline-flex items-baseline gap-2 font-semibold text-ink', SIZES[size] ?? SIZES.md, className].filter(Boolean).join(' ')}>
            <span>{money(price)}<span className="ml-1 text-[0.75em] font-medium text-ink-3">{currency}</span></span>
            {onSale && <span className="text-[0.8em] font-normal text-ink-3 line-through">{money(compareAt)}</span>}
        </span>
    );
}
