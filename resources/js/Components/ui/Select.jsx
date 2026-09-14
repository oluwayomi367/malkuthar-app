/**
 * Équivalent React de `x-ui.select` — chevron personnalisé, mêmes classes.
 * `options` : { value: label } ou tableau [{ value, label }].
 */
export default function Select({
    name, label, options = {}, placeholder, required = false, error, className = '', ...rest
}) {
    const id = rest.id || name;
    const entries = Array.isArray(options)
        ? options.map((o) => [o.value, o.label ?? o.value])
        : Object.entries(options);

    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <label htmlFor={id} className="text-[13px] font-medium text-ink-2">
                    {label}{required && <span className="text-accent"> *</span>}
                </label>
            )}
            <div className="relative">
                <select
                    id={id}
                    name={name}
                    required={required}
                    className={[
                        'h-11 w-full appearance-none rounded-xl border bg-surface pl-3.5 pr-9 text-[15px] text-ink',
                        'transition-colors focus:outline-none focus:ring-2',
                        error ? 'border-rust/60 focus:ring-rust/20' : 'border-black/10 focus:border-accent focus:ring-accent/25',
                        className,
                    ].filter(Boolean).join(' ')}
                    {...rest}
                >
                    {placeholder && <option value="">{placeholder}</option>}
                    {entries.map(([value, label2]) => (
                        <option key={value} value={value}>{label2}</option>
                    ))}
                </select>
                <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
                    <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </div>
            {error && <p className="text-xs text-rust">{error}</p>}
        </div>
    );
}
