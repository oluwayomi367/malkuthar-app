const TONES = {
    info: 'border-line-strong bg-surface text-ink-2',
    success: 'border-sage/30 bg-sage-soft text-sage',
    warning: 'border-amber/30 bg-amber-tint text-amber',
    error: 'border-rust/30 bg-rust-tint text-rust',
};

export default function Alert({ tone = 'info', className = '', children }) {
    return (
        <div role={tone === 'error' ? 'alert' : 'status'} className={[
            'rounded-xl border px-4 py-3 text-sm leading-relaxed',
            TONES[tone] ?? TONES.info,
            className,
        ].filter(Boolean).join(' ')}>
            {children}
        </div>
    );
}
