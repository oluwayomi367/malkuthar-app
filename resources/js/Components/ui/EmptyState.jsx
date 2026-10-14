const PATHS = {
    box: 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10',
    search: 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-4.3-4.3',
    cart: 'M3 4h2l1.6 9.2A1.5 1.5 0 0 0 8 14.4h8.8a1.5 1.5 0 0 0 1.47-1.2L19.5 7H6M8.5 19.5a1 1 0 1 0 0 .01M16.5 19.5a1 1 0 1 0 0 .01',
    image: 'M4 5h16v14H4zM4 15l4-4 4 4 4-6 4 6',
    wallet: 'M3 7h15a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h11M17 12h.01',
    inbox: 'M4 13h4l1 3h6l1-3h4M4 13l2-8h12l2 8v6H4z',
    star: 'M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 18.8 6.2 21.9l1.1-6.5L2.6 9.8l6.5-.9z',
};

export default function EmptyState({ icon, title = 'Rien à afficher', text, cta, className = '' }) {
    return (
        <div className={['rounded-2xl border border-line bg-surface px-6 py-12 text-center', className].filter(Boolean).join(' ')}>
            <svg className="mx-auto h-7 w-7 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d={PATHS[icon] ?? PATHS.box} />
            </svg>
            <p className="mt-4 text-base font-semibold text-ink">{title}</p>
            {text && <p className="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-ink-2">{text}</p>}
            {cta && <div className="mt-6 flex flex-wrap justify-center gap-2">{cta}</div>}
        </div>
    );
}
