import { Link } from '@inertiajs/react';

const VARIANTS = {
    primary: 'text-white bg-accent shadow-[0_1px_2px_rgba(46,44,40,0.12)] hover:bg-[#77602a] active:bg-[#6a5624]',
    secondary: 'text-ink bg-surface border border-line-strong hover:bg-ground-alt hover:border-ink-3',
    ghost: 'text-accent px-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent',
};

/** Équivalent React de `x-ui.button` — mêmes classes, même rendu. */
export default function Button({
    variant = 'primary', href, type = 'button', full = false, disabled = false,
    className = '', native = false, children, ...rest
}) {
    const classes = [
        'group relative inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold',
        'transition-[transform,box-shadow,background-color,opacity] duration-200',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
        'disabled:pointer-events-none disabled:opacity-40 h-11 px-5 sm:h-10',
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
