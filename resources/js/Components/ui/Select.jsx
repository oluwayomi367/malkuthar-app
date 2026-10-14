import { FIELD_CONTROL, FieldError, FieldLabel, fieldState } from './Field';

/**
 * Équivalent React de `x-ui.select` — chevron personnalisé, mêmes classes.
 * `options` : { value: label } ou tableau [{ value, label }].
 */
export default function Select({
    name, label, options = {}, placeholder, required = false, error, icon, hint, className = '', selectClassName = '', ...rest
}) {
    const id = rest.id || name;
    const errorId = error ? `${id}-error` : undefined;
    const entries = Array.isArray(options)
        ? options.map((o) => [o.value, o.label ?? o.value])
        : Object.entries(options);

    return (
        <div className={['flex flex-col gap-1.5', className].filter(Boolean).join(' ')}>
            <FieldLabel id={id} label={label} required={required} hint={hint} />
            <div className="relative flex items-center">
                {icon && (
                    <span className="pointer-events-none absolute left-3.5 flex items-center justify-center text-ink-3">
                        {icon}
                    </span>
                )}
                <select
                    id={id}
                    name={name}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={errorId}
                    className={[
                        FIELD_CONTROL,
                        fieldState(error),
                        'cursor-pointer appearance-none pr-10',
                        icon ? 'pl-10' : 'pl-3.5',
                        selectClassName,
                    ].filter(Boolean).join(' ')}
                    {...rest}
                >
                    {placeholder && <option value="">{placeholder}</option>}
                    {entries.map(([value, label2]) => (
                        <option key={value} value={value}>{label2}</option>
                    ))}
                </select>
                <span className="pointer-events-none absolute right-3 flex items-center text-ink-3" aria-hidden="true">
                    <svg className="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.7">
                        <path d="M6 8l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </span>
            </div>
            <FieldError id={errorId} error={error} />
        </div>
    );
}
