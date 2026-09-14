const TONES = {
    ink: 'border-black/15 bg-black/[0.04] text-ink',
    accent: 'border-accent/30 bg-accent/10 text-accent',
    gold: 'border-gold/40 bg-gold/10 text-gold',
    rust: 'border-rust/30 bg-rust/10 text-rust',
    muted: 'border-black/10 text-ink-3',
};

export default function Badge({ tone = 'ink', className = '', children }) {
    return (
        <span className={[
            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5',
            'text-[10.5px] font-semibold uppercase tracking-[0.07em] backdrop-blur',
            TONES[tone] ?? TONES.ink,
            className,
        ].filter(Boolean).join(' ')}>
            {children}
        </span>
    );
}
