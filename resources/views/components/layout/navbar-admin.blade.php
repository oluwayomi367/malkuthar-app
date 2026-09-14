@php $user = Auth::user(); @endphp
<header class="sticky top-0 z-40 border-b border-black/[0.06] bg-ground/70 backdrop-blur-xl">
    <div class="mx-auto flex h-14 max-w-[1280px] items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-2.5">
            <span class="font-display text-[15px] font-bold tracking-[0.16em] text-ink">MALKUTHAR</span>
            <span class="rounded-md border border-accent/40 bg-accent/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-accent">Admin</span>
        </div>

        <div class="flex items-center gap-3 text-sm">
            <a href="{{ url('/') }}" class="hidden text-ink-2 transition-colors hover:text-ink sm:inline">Voir le site ↗</a>
            <span class="hidden text-ink-3 sm:inline">{{ $user?->email }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="rounded-lg border border-black/10 bg-black/[0.04] px-3 py-1.5 text-ink transition-colors hover:bg-black/[0.06]">Quitter</button>
            </form>
        </div>
    </div>
</header>
