/** Équivalent React de `x-ui.field`. Champ contrôlé : passer `value`/`onChange`. */
export default function Field({
    name, label, type = 'text', hint, required = false, error, className = '', ...rest
}) {
    const id = rest.id || name;

    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <label htmlFor={id} className="text-[13px] font-medium text-ink-2">
                    {label}{required && <span className="text-accent"> *</span>}
                </label>
            )}
            {hint && <p className="-mt-0.5 text-xs text-ink-3">{hint}</p>}
            <input
                id={id}
                name={name}
                type={type}
                required={required}
                className={[
                    'h-11 rounded-xl border bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3',
                    'transition-colors focus:outline-none focus:ring-2',
                    error ? 'border-rust/60 focus:border-rust focus:ring-rust/20' : 'border-black/10 focus:border-accent focus:ring-accent/25',
                    className,
                ].filter(Boolean).join(' ')}
                {...rest}
            />
            {error && <p className="text-xs text-rust">{error}</p>}
        </div>
    );
}
