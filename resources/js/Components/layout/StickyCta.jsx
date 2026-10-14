export default function StickyCta({ info, children }) {
    return (
        <>
            <div data-sticky-cta className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface px-4 py-3 lg:hidden"
                 style={{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }}>
                <div className="mx-auto flex max-w-[1280px] items-center gap-3">
                    {info && <div className="min-w-0 flex-1 text-sm text-ink">{info}</div>}
                    <div className={info ? 'shrink-0' : 'flex-1'}>{children}</div>
                </div>
            </div>
            <div className="h-20 lg:hidden" aria-hidden="true" />
        </>
    );
}
