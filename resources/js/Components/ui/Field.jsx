/** Équivalent React de `x-ui.field`. Champ contrôlé : passer `value`/`onChange`. */
export const FIELD_CONTROL = [
    'min-h-11 w-full rounded-xl border bg-surface text-[15px] text-ink placeholder:text-ink-3',
    'transition-colors duration-150 focus:outline-none focus:ring-2',
].join(' ');

export const fieldState = (error) => (error
    ? 'border-rust focus:border-rust focus:ring-rust/20'
    : 'border-line-strong hover:border-ink-3 focus:border-accent focus:ring-accent/25');

export function FieldLabel({ id, label, required, hint }) {
    if (!label) return null;
    return (
        <div className="flex items-baseline justify-between gap-3">
            <label htmlFor={id} className="text-sm font-medium text-ink">
                {label}
                {required && <span className="ml-0.5 text-accent-2" aria-hidden="true">*</span>}
            </label>
            {hint && <span className="text-xs text-ink-3">{hint}</span>}
        </div>
    );
}

export function FieldError({ id, error }) {
    if (!error) return null;
    return <p id={id} className="text-[13px] font-medium text-rust">{error}</p>;
}

export default function Field({
    name, label, type = 'text', hint, required = false, error, icon, suffix, className = '', inputClassName = '', ...rest
}) {
    const id = rest.id || name;
    const errorId = error ? `${id}-error` : undefined;

    return (
        <div className={['flex flex-col gap-1.5', className].filter(Boolean).join(' ')}>
            <FieldLabel id={id} label={label} required={required} hint={hint} />
            <div className="relative flex items-center">
                {icon && (
                    <span className="pointer-events-none absolute left-3.5 flex items-center justify-center text-ink-3">
                        {icon}
                    </span>
                )}
                <input
                    id={id}
                    name={name}
                    type={type}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={errorId}
                    className={[
                        FIELD_CONTROL,
                        fieldState(error),
                        icon ? 'pl-10' : 'pl-3.5',
                        suffix ? 'pr-12' : 'pr-3.5',
                        inputClassName,
                    ].filter(Boolean).join(' ')}
                    {...rest}
                />
                {suffix && (
                    <span className="pointer-events-none absolute right-3.5 text-sm text-ink-3">
                        {suffix}
                    </span>
                )}
            </div>
            <FieldError id={errorId} error={error} />
        </div>
    );
}
