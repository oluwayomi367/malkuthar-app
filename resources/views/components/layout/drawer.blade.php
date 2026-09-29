<div
    x-data
    x-show="$store.drawer.open"
    x-cloak
    class="fixed inset-0 z-50 lg:hidden"
    x-on:keydown.escape.window="$store.drawer.close()"
>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="$store.drawer.close()"></div>

    <div
        class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-line-strong/80 bg-surface shadow-2xl"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <div class="flex h-18 items-center justify-between border-b border-line px-5">
            <a href="{{ url('/') }}" class="flex items-center gap-3" x-on:click="$store.drawer.close()">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/70 bg-white p-1 shadow-2xs">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo MALKUTHAR" class="h-full w-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="font-display text-[15.5px] font-extrabold tracking-[0.16em] text-ink leading-tight">MALKUTHAR</span>
                    <span class="text-[9px] font-extrabold tracking-[0.22em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
                </div>
            </a>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-ground-alt/60 text-ink hover:bg-ground-alt"
                    x-on:click="$store.drawer.close()" aria-label="Fermer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-4" x-data="{ prod: {{ request()->is('boutique*', 'produits*') ? 'true' : 'false' }} }">
            <a href="{{ url('/') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">Accueil</a>

            <div>
                <button type="button" x-on:click="prod = !prod"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-ink hover:bg-ground-alt">
                    <span>Produits</span>
                    <svg class="h-4 w-4 text-ink-3 transition-transform" :class="prod && 'rotate-180 text-accent'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div x-show="prod" x-cloak class="my-1 ml-3 space-y-1 border-l-2 border-accent/40 pl-3">
                    <a href="{{ url('/boutique') }}" class="block rounded-lg px-2.5 py-2 text-xs font-bold text-accent hover:bg-ground-alt" x-on:click="$store.drawer.close()">Toute la boutique →</a>
                    @foreach (\App\Support\Catalog::categories() as $c)
                        <a href="{{ route('catalog.index', ['type' => $c['type']]) }}" class="block rounded-lg px-2.5 py-2 text-xs font-medium text-ink-2 hover:bg-ground-alt hover:text-ink" x-on:click="$store.drawer.close()">{{ $c['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('affiliation') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">Affiliation</a>
            <a href="{{ \App\Support\MarketplaceSettings::enabled() ? route('marketplace.shop') : route('marketplace') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">
                <span>Marketplace</span>
                <span class="rounded-md bg-accent/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-accent">Place</span>
            </a>
            <a href="{{ route('about') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">À propos</a>
            <a href="{{ route('contact') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">Contact</a>

            <div class="my-3 border-t border-line"></div>
            @auth
                <div class="space-y-1">
                    <a href="{{ url('/mon-compte') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-bold text-ink hover:bg-ground-alt" x-on:click="$store.drawer.close()">Mon compte</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rust hover:bg-rust/10">Se déconnecter</button>
                    </form>
                </div>
            @else
                <div class="space-y-2 pt-2">
                    <a href="{{ route('login') }}" class="flex w-full items-center justify-center rounded-xl border border-line-strong/80 bg-white py-2.5 text-xs font-bold uppercase tracking-wider text-ink shadow-2xs hover:bg-ground-alt" x-on:click="$store.drawer.close()">Connexion</a>
                    <a href="{{ route('register') }}" class="flex w-full items-center justify-center rounded-xl bg-ink py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-2xs hover:bg-ink/90" x-on:click="$store.drawer.close()">Créer un compte</a>
                </div>
            @endauth
        </nav>

        <div class="border-t border-line bg-ground-alt/40 px-5 py-3 text-xs text-ink-3">
            <a href="{{ route('affiliate.program-terms') }}" class="font-medium hover:text-ink">Conditions du programme</a>
        </div>
    </div>
</div>
