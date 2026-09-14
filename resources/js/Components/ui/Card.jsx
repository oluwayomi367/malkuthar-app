import { Link } from '@inertiajs/react';

export default function Card({ href, padded = true, className = '', children, ...rest }) {
    const classes = [
        'block rounded-2xl border border-line bg-surface',
        'shadow-[0_1px_2px_rgba(46,44,40,0.04),0_10px_30px_-24px_rgba(46,44,40,0.16)]',
        padded ? 'p-5 sm:p-6' : '',
        href ? 'transition-[box-shadow,border-color] hover:border-line-strong hover:shadow-[0_2px_4px_rgba(46,44,40,0.05),0_16px_36px_-24px_rgba(46,44,40,0.22)]' : '',
        className,
    ].filter(Boolean).join(' ');

    if (href) {
        return <Link href={href} className={classes} {...rest}>{children}</Link>;
    }

    return <div className={classes} {...rest}>{children}</div>;
}
