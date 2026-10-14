import { Link } from '@inertiajs/react';

const VARIANTS = {
    primary: 'text-white bg-accent hover:bg-accent-2 active:bg-accent-2',
    secondary: 'text-ink bg-surface border border-line-strong hover:border-ink hover:bg-surface',
    ghost: 'text-accent px-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent',
};

/** Équivalent React de `x-ui.button` — mêmes classes, même rendu. */
export default function Button({
    variant = 'primary', href, type = 'button', full = false, disabled = false,
    className = '', native = false, children, ...rest
}) {
    const classes = [
        'group relative inline-flex items-center justify-center gap-2 rounded-full text-sm font-semibold',
        'transition-[transform,box-shadow,background-color,opacity] duration-200',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
        'disabled:pointer-events-none disabled:opacity-40 min-h-11 px-6',
        VARIANTS[variant] ?? VARIANTS.primary,
        full ? 'w-full' : '',
        className,
    ].filter(Boolean).join(' ');

    if (href) {
        return native
            ? <a href={href} className={classes} {...rest}>{children}</a>
            : <Link href={href} className={classes} {...rest}>{children}</Link>;
    }

    return (
        <button type={type} disabled={disabled} className={classes} {...rest}>
            {children}
        </button>
    );
}
