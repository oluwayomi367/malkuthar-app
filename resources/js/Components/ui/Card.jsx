import { Link } from '@inertiajs/react';

export default function Card({ href, padded = true, hoverable = false, className = '', children, ...rest }) {
    const isInteractive = Boolean(href || hoverable);

    const classes = [
        'block rounded-2xl border border-line bg-surface',
        'shadow-[0_1px_2px_rgba(28,25,23,0.04)]',
        padded ? 'p-5 sm:p-6' : '',
        isInteractive
            ? 'transition-all duration-300 hover:border-line-strong hover:shadow-[0_16px_32px_-20px_rgba(28,25,23,0.18)]'
            : '',
        className,
    ].filter(Boolean).join(' ');

    if (href) {
        return <Link href={href} className={classes} {...rest}>{children}</Link>;
    }

    return <div className={classes} {...rest}>{children}</div>;
}

