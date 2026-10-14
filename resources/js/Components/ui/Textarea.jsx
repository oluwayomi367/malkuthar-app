import { FIELD_CONTROL, FieldError, FieldLabel, fieldState } from './Field';

export default function Textarea({
    name, label, rows = 5, hint, required = false, error, className = '', textareaClassName = '', ...rest
}) {
    const id = rest.id || name;
    const errorId = error ? `${id}-error` : undefined;

    return (
        <div className={['flex flex-col gap-1.5', className].filter(Boolean).join(' ')}>
            <FieldLabel id={id} label={label} required={required} hint={hint} />
            <textarea
                id={id}
                name={name}
                rows={rows}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={errorId}
                className={[
                    FIELD_CONTROL,
                    fieldState(error),
                    'px-3.5 py-3 leading-relaxed',
                    textareaClassName,
                ].filter(Boolean).join(' ')}
                {...rest}
            />
            <FieldError id={errorId} error={error} />
        </div>
    );
}
