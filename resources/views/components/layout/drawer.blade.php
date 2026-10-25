@php
    $mpEnabled = \App\Support\MarketplaceSettings::enabled();
    $item = 'flex min-h-11 items-center rounded-xl px-3 text-[15px] font-medium text-ink hover:bg-ground-alt';
@endphp

<div
    x-data
    x-show="$store.drawer.open"
    x-cloak
    class="fixed inset-0 z-50 lg:hidden"
    x-on:keydown.escape.window="$store.drawer.close()"
    role="dialog"
    aria-modal="true"
    aria-label="Menu"
>
    <div class="absolute inset-0 bg-deep/50" x-on:click="$store.drawer.close()"></div>

    <div
        class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-line bg-ground"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <div class="flex h-[72px] items-center justify-between border-b border-line px-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5" x-on:click="$store.drawer.close()">
                <img src="{{ \App\Support\StorefrontImages::url('brand.logo') }}" alt="" class="h-8 w-8 object-contain">
                <span class="font-display text-[20px] font-semibold tracking-[0.18em] text-ink">MALKUTHAR</span>
            </a>
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt"
                    x-on:click="$store.drawer.close()" aria-label="Fermer le menu">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto p-3" aria-label="Navigation principale">
            <a href="{{ url('/boutique') }}" class="{{ $item }}" x-on:click="$store.drawer.close()">Boutique</a>
            <div class="mb-2 ml-3 space-y-0.5 border-l border-line pl-3">
                @foreach (\App\Support\Catalog::categories() as $c)
                    <a href="{{ route('catalog.index', ['type' => $c['type']]) }}" class="flex min-h-11 items-center rounded-lg px-2.5 text-sm text-ink-2 hover:bg-ground-alt hover:text-ink" x-on:click="$store.drawer.close()">{{ $c['label'] }}</a>
                @endforeach
            </div>
            <a href="{{ $mpEnabled ? route('marketplace.shop') : route('marketplace') }}" class="{{ $item }}" x-on:click="$store.drawer.close()">Place de marché</a>
            <a href="{{ route('affiliation') }}" class="{{ $item }}" x-on:click="$store.drawer.close()">Affiliation</a>
            <a href="{{ route('about') }}" class="{{ $item }}" x-on:click="$store.drawer.close()">À propos</a>
            <a href="{{ route('contact') }}" class="{{ $item }}" x-on:click="$store.drawer.close()">Contact</a>

            <div class="!my-4 border-t border-line"></div>

            @auth
                <a href="{{ url('/espace') }}" class="{{ $item }} font-semibold" x-on:click="$store.drawer.close()">Mon espace</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="flex min-h-11 w-full items-center rounded-xl px-3 text-left text-[15px] font-medium text-rust hover:bg-ground-alt">Se déconnecter</button>
                </form>
            @else
                <div class="grid gap-2 px-1 pt-1">
                    <x-ui.button variant="secondary" :href="route('login')" :full="true">Connexion</x-ui.button>
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-deep px-6 text-sm font-semibold text-white hover:bg-deep-2">Créer un compte</a>
                </div>
            @endauth
        </nav>

        <div class="border-t border-line px-4 py-3">
            <a href="{{ route('affiliate.program-terms') }}" class="inline-flex min-h-11 items-center text-sm text-ink-2 hover:text-ink">Conditions du programme</a>
        </div>
    </div>
</div>
