@php $user = Auth::user(); @endphp
<a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-night-2 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Aller au contenu</a>

<header class="sticky top-0 z-40 border-b border-night-line bg-night/95 backdrop-blur" x-data="{ menu: false }">
    <div class="mx-auto flex h-16 max-w-[1440px] items-center gap-3 px-4 sm:px-6">
        <button type="button" x-on:click="menu = !menu" x-bind:aria-expanded="menu" aria-controls="admin-menu-mobile" aria-label="Ouvrir le menu"
                class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-night-line text-night-ink lg:hidden">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
            <span class="font-display text-[22px] font-semibold tracking-[0.14em] text-night-ink">MALKUTHAR</span>
            <span class="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-accent-light">Console</span>
        </a>

        <div class="ml-auto flex items-center gap-2 text-sm">
            <a href="{{ url('/') }}" class="hidden min-h-11 items-center rounded-lg px-3 text-night-ink-2 transition-colors hover:text-night-ink sm:inline-flex">Voir le site</a>
            <span class="hidden text-night-ink-2 md:inline">{{ $user?->email }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="min-h-11 rounded-lg border border-night-line px-4 text-night-ink transition-colors hover:bg-night-2">Se déconnecter</button>
            </form>
        </div>
    </div>

    <div id="admin-menu-mobile" x-show="menu" x-cloak x-on:keydown.escape.window="menu = false"
         class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-night-line bg-night px-4 py-4 lg:hidden">
        <x-layout.sidebar-admin variant="drawer" />
    </div>
</header>
