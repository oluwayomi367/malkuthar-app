export default function SectionHeading({ eyebrow, title, action, className = '', children }) {
    return (
        <div className={['mb-4 flex items-end justify-between gap-4', className].filter(Boolean).join(' ')}>
            <div>
                {eyebrow && <p className="text-xs font-semibold uppercase tracking-[0.14em] text-accent">{eyebrow}</p>}
                {title && <h2 className="mt-1 font-display text-2xl font-semibold text-ink sm:text-[28px]">{title}</h2>}
                {children}
            </div>
            {action && <div className="shrink-0 text-sm text-ink-2">{action}</div>}
        </div>
    );
}
