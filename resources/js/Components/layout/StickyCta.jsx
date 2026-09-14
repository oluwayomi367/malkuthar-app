export default function StickyCta({ info, children }) {
    return (
        <>
            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-black/[0.08] bg-ground/85 px-4 py-3 backdrop-blur-xl lg:hidden"
                 style={{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }}>
                <div className="mx-auto flex max-w-[1180px] items-center gap-3">
                    {info && <div className="min-w-0 flex-1 text-sm">{info}</div>}
                    <div className={info ? 'shrink-0' : 'flex-1'}>{children}</div>
                </div>
            </div>
            <div className="h-20 lg:hidden" aria-hidden="true" />
        </>
    );
}
