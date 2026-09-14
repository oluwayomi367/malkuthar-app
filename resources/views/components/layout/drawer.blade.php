<div
    x-data
    x-show="$store.drawer.open"
    x-cloak
    class="fixed inset-0 z-50 lg:hidden"
    x-on:keydown.escape.window="$store.drawer.close()"
>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="$store.drawer.close()"></div>

    <div
        class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-black/[0.08] bg-ground/95 backdrop-blur-xl"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <div class="flex h-14 items-center justify-between border-b border-black/[0.06] px-4">
            <span class="font-display text-[15px] font-bold tracking-[0.18em] text-ink">MALKUTHAR</span>
            <button type="button" class="-mr-2 inline-flex h-11 w-11 items-center justify-center text-ink"
                    x-on:click="$store.drawer.close()" aria-label="Fermer">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-2" x-data="{ prod: {{ request()->is('boutique*', 'produits*') ? 'true' : 'false' }} }">
            <a href="{{ url('/') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Accueil</a>

            <button type="button" x-on:click="prod = !prod"
                    class="flex w-full items-center justify-between rounded-lg px-2 py-3 text-left text-[15px] text-ink hover:bg-black/[0.04]">
                Produits
                <svg class="h-4 w-4 text-ink-3 transition-transform" :class="prod && 'rotate-180'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div x-show="prod" x-cloak class="ml-2 border-l border-line pl-3">
                <a href="{{ url('/boutique') }}" class="block rounded-lg px-2 py-2.5 text-sm text-ink-2 hover:bg-black/[0.04] hover:text-ink">Toute la boutique</a>
                @foreach (\App\Support\Catalog::categories() as $c)
                    <a href="{{ route('catalog.index', ['type' => $c['type']]) }}" class="block rounded-lg px-2 py-2.5 text-sm text-ink-2 hover:bg-black/[0.04] hover:text-ink">{{ $c['label'] }}</a>
                @endforeach
            </div>

            <a href="{{ route('affiliation') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Affiliation</a>
            <a href="{{ \App\Support\MarketplaceSettings::enabled() ? route('marketplace.shop') : route('marketplace') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Marketplace</a>
            <a href="{{ route('about') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">À propos</a>
            <a href="{{ route('contact') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Contact</a>

            <div class="my-2 border-t border-black/[0.06]"></div>
            @auth
                <a href="{{ url('/mon-compte') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Mon compte</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block w-full rounded-lg px-2 py-3 text-left text-[15px] text-ink hover:bg-black/[0.04]">Se déconnecter</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Connexion</a>
                <a href="{{ route('register') }}" class="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Créer un compte</a>
            @endauth
        </nav>

        <div class="border-t border-black/[0.06] px-4 py-3 text-xs text-ink-3">
            <a href="{{ route('affiliate.program-terms') }}" class="hover:text-ink">Conditions</a>
        </div>
    </div>
</div>
