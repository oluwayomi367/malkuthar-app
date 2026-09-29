import { Link } from '@inertiajs/react';

export default function Card({ href, padded = true, hoverable = false, className = '', children, ...rest }) {
    const isInteractive = Boolean(href || hoverable);

    const classes = [
        'block rounded-2xl border border-line/80 bg-surface',
        'shadow-[0_1px_3px_rgba(46,44,40,0.03),0_12px_28px_-20px_rgba(46,44,40,0.1)]',
        padded ? 'p-5 sm:p-6' : '',
        isInteractive
            ? 'transition-all duration-300 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-[0_4px_16px_rgba(46,44,40,0.06),0_24px_48px_-20px_rgba(46,44,40,0.16)]'
            : '',
        className,
    ].filter(Boolean).join(' ');

    if (href) {
        return <Link href={href} className={classes} {...rest}>{children}</Link>;
    }

    return <div className={classes} {...rest}>{children}</div>;
}

