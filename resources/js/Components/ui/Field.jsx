/** Équivalent React de `x-ui.field`. Champ contrôlé : passer `value`/`onChange`. */
export default function Field({
    name, label, type = 'text', hint, required = false, error, icon, suffix, className = '', inputClassName = '', ...rest
}) {
    const id = rest.id || name;

    return (
        <div className={['group flex flex-col gap-1.5', className].filter(Boolean).join(' ')}>
            {label && (
                <div className="flex items-center justify-between">
                    <label htmlFor={id} className="text-[12.5px] font-semibold tracking-wide text-ink-2 transition-colors group-focus-within:text-accent">
                        {label}{required && <span className="ml-1 text-accent font-bold" title="Requis">*</span>}
                    </label>
                    {hint && <span className="text-[11px] text-ink-3">{hint}</span>}
                </div>
            )}
            <div className="relative flex items-center">
                {icon && (
                    <span className="pointer-events-none absolute left-3.5 flex items-center justify-center text-ink-3 transition-colors group-focus-within:text-accent">
                        {icon}
                    </span>
                )}
                <input
                    id={id}
                    name={name}
                    type={type}
                    required={required}
                    className={[
                        'h-11 w-full rounded-xl border bg-surface text-[14.5px] text-ink placeholder:text-ink-3/70',
                        'shadow-[0_1px_2px_rgba(46,44,40,0.03),inset_0_1px_1px_rgba(255,255,255,0.85)]',
                        'transition-all duration-200 focus:outline-none focus:ring-4',
                        icon ? 'pl-10' : 'pl-3.5',
                        suffix ? 'pr-12' : 'pr-3.5',
                        error
                            ? 'border-rust/70 bg-rust/[0.015] focus:border-rust focus:ring-rust/15'
                            : 'border-line/90 hover:border-line-strong focus:border-accent focus:bg-surface focus:ring-accent/15 focus:shadow-[0_0_0_1px_var(--color-accent),0_2px_8px_rgba(138,109,44,0.08)]',
                        inputClassName,
                    ].filter(Boolean).join(' ')}
                    {...rest}
                />
                {suffix && (
                    <span className="pointer-events-none absolute right-3.5 text-xs font-semibold text-ink-3">
                        {suffix}
                    </span>
                )}
            </div>
            {error && (
                <p className="flex items-center gap-1 text-[11.5px] font-medium text-rust">
                    <svg className="h-3.5 w-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor">
                        <path fillRule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm-.75-4.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0zM8 4a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4A.75.75 0 0 1 8 4z" clipRule="evenodd" />
                    </svg>
                    <span>{error}</span>
                </p>
            )}
        </div>
    );
}

