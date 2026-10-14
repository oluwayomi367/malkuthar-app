const TONES = {
    ink: 'border-line-strong bg-ground-alt text-ink',
    accent: 'border-accent/25 bg-accent-soft text-accent-2',
    gold: 'border-accent/25 bg-accent-soft text-accent-2',
    rust: 'border-rust/30 bg-rust/10 text-rust',
    muted: 'border-line text-ink-2',
};

export default function Badge({ tone = 'ink', className = '', children }) {
    return (
        <span className={[
            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5',
            'text-xs font-semibold',
            TONES[tone] ?? TONES.ink,
            className,
        ].filter(Boolean).join(' ')}>
            {children}
        </span>
    );
}
