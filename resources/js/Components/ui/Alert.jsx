const TONES = {
    info: 'border-line bg-ground-alt text-ink-2',
    success: 'border-brand-green/30 bg-brand-green/[0.08] text-brand-green',
    warning: 'border-amber/40 bg-amber/[0.10] text-amber',
    error: 'border-rust/30 bg-rust/[0.08] text-rust',
};

export default function Alert({ tone = 'info', className = '', children }) {
    return (
        <div role="status" className={[
            'rounded-xl border px-4 py-3 text-sm backdrop-blur',
            TONES[tone] ?? TONES.info,
            className,
        ].filter(Boolean).join(' ')}>
            {children}
        </div>
    );
}
