{{-- En-tête public des pages Blade — miroir de l'en-tête React (resources/js/Layouts/StorefrontLayout.jsx).
     Toute modification de l'un doit être reportée dans l'autre. --}}
@php
    $mpEnabled = \App\Support\MarketplaceSettings::enabled();
    $marketplaceHref = $mpEnabled ? route('marketplace.shop') : route('marketplace');
    $categories = \App\Support\Catalog::categories();

    // Bandeau d'annonce : segments séparés par retour à la ligne, « | » ou « · », sans pictogrammes.
    $ticker = \App\Support\StorefrontSettings::announcementTicker()
        ?: 'Téléchargement immédiat des produits numériques | Expédition suivie à domicile | Paiement Mobile Money et carte bancaire';
    $tickerSegments = collect(preg_split('/\r?\n|\||·/u', $ticker))
        ->map(fn ($s) => trim(preg_replace('/\s{2,}/u', ' ', preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2726}\x{2727}\x{FE0F}\x{200D}]/u', '', $s))))
        ->filter()
        ->values();
    $showTicker = \App\Support\StorefrontSettings::announcementTickerEnabled() && $tickerSegments->isNotEmpty();

    // Univers mis en avant dans la barre de catégories (mêmes entrées que UNIVERS_NAV côté React).
    $universNav = [
        ['Formations', url('/boutique?type=digital'), request()->is('boutique') && request('type') === 'digital'],
        ['Soins & Praticiens', url('/boutique?type=service'), request()->is('boutique') && request('type') === 'service'],
    ];
    $navLink = fn ($active) => 'relative inline-flex min-h-11 items-center whitespace-nowrap px-3 text-sm font-medium transition-colors '
        .($active ? 'text-ink after:absolute after:inset-x-3 after:bottom-2 after:h-px after:bg-accent' : 'text-ink-2 hover:text-ink');
    $firstName = auth()->check() ? explode(' ', trim(auth()->user()->name ?? ''))[0] : null;
@endphp

<a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-deep focus:px-5 focus:py-3 focus:text-sm focus:text-white">
    Aller au contenu
</a>

@if ($showTicker)
    <aside x-data="{ visible: true }" x-show="visible" aria-label="Annonces" class="relative border-b border-line bg-ground-alt text-ink-2">
        <div class="mx-auto flex min-h-10 max-w-[1280px] items-center justify-center px-12 text-center text-[13px]">
            <p class="truncate sm:hidden">{{ $tickerSegments->first() }}</p>
            <p class="hidden truncate sm:block">{{ $tickerSegments->join(' · ') }}</p>
        </div>
        <button type="button" x-on:click="visible = false" aria-label="Fermer le bandeau d'annonce"
                class="absolute right-1 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-ink-3 hover:text-ink">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </aside>
@endif

<header class="sticky top-0 z-40" x-data="{ mega: false }" x-on:keydown.escape.window="mega = false">
    {{-- Barre principale : marque, recherche, compte, panier. --}}
    <div class="bg-deep text-white">
        <div class="mx-auto flex h-16 max-w-[1280px] items-center gap-3 px-4 sm:px-6 lg:h-[76px] lg:gap-8">
            <button type="button" x-on:click="$store.drawer.toggle()" aria-label="Ouvrir le menu"
                    class="-ml-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white hover:bg-white/10 lg:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2.5" aria-label="MALKUTHAR, accueil">
                <img src="{{ \App\Support\StorefrontImages::url('brand.logo') }}" alt="" class="h-9 w-9 object-contain">
                <span class="font-display text-[22px] font-semibold leading-none tracking-[0.14em] text-white">MALKUTHAR</span>
            </a>

            @include('partials.search-form', ['searchId' => 'recherche-entete', 'searchClass' => 'hidden flex-1 lg:flex lg:max-w-[620px]'])

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <a href="{{ auth()->check() ? url('/espace') : route('login') }}"
                   aria-label="{{ auth()->check() ? 'Mon espace' : 'Se connecter' }}"
                   class="inline-flex min-h-11 items-center gap-2.5 rounded-full px-2 text-sm text-white transition-colors hover:bg-white/10 sm:px-3">
                    <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-1.5a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4V21"/><path d="M12 11.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                    </svg>
                    <span class="hidden flex-col leading-tight sm:flex" aria-hidden="true">
                        <span class="text-xs text-white/70">{{ $firstName ? 'Bonjour '.$firstName : 'Bienvenue' }}</span>
                        <span class="font-semibold">{{ auth()->check() ? 'Mon espace' : 'Se connecter' }}</span>
                    </span>
                </a>

                <a href="{{ route('cart') }}" aria-label="Panier"
                   class="relative -mr-2 inline-flex min-h-11 items-center gap-2.5 rounded-full px-2 text-sm text-white transition-colors hover:bg-white/10 sm:mr-0 sm:px-3">
                    <span class="relative">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 4h2.2l1.9 10.2a1.6 1.6 0 0 0 1.6 1.3h8.1a1.6 1.6 0 0 0 1.6-1.2L20 8H6.2"/><path d="M9 20h.01"/><path d="M17 20h.01"/>
                        </svg>
                        <span x-data x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count" aria-hidden="true"
                              class="absolute -right-2 -top-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-light px-1 text-xs font-bold text-ink"></span>
                    </span>
                    <span class="hidden font-semibold sm:inline" aria-hidden="true">Panier</span>
                </a>
            </div>
        </div>

        {{-- Recherche pleine largeur sur mobile. --}}
        <div class="px-4 pb-3 sm:px-6 lg:hidden">
            @include('partials.search-form', ['searchId' => 'recherche-mobile', 'searchClass' => ''])
        </div>
    </div>

    {{-- Barre de catégories. --}}
    <div class="relative hidden border-b border-line bg-surface lg:block">
        <nav class="mx-auto flex h-12 max-w-[1280px] items-center justify-center gap-1 px-6" aria-label="Catégories">
            <button type="button" x-on:click="mega = !mega" x-on:mouseenter="mega = true" x-bind:aria-expanded="mega" aria-controls="menu-boutique"
                    class="mr-2 inline-flex min-h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-full bg-ground-alt px-4 text-sm font-semibold text-ink transition-colors hover:bg-line">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v6H4zM14 15h6v6h-6z"/></svg>
                Toutes les catégories
                <svg class="h-4 w-4 text-ink-3 transition-transform" x-bind:class="mega && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            @foreach ($universNav as [$label, $href, $active])
                <a href="{{ $href }}" @if ($active) aria-current="page" @endif class="{{ $navLink($active) }}">{{ $label }}</a>
            @endforeach
            <span class="mx-2 h-5 w-px bg-line-strong" aria-hidden="true"></span>
            <a href="{{ $marketplaceHref }}" class="{{ $navLink(request()->is('place-de-marche*', 'marketplace*')) }}">Place de marché</a>
            <a href="{{ route('affiliation') }}" class="{{ $navLink(request()->is('affiliation*', 'programme-affiliation*')) }}">Affiliation</a>
            <span class="mx-2 h-5 w-px bg-line-strong" aria-hidden="true"></span>
            <a href="{{ route('marketplace.become-vendor') }}"
               class="inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap px-3 text-sm font-semibold text-space-vendor hover:text-ink">
                Vendre sur MALKUTHAR
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </nav>

        {{-- Méga-menu « Toutes les catégories ». --}}
        <div id="menu-boutique" x-show="mega" x-cloak x-on:mouseleave="mega = false" x-on:click.outside="mega = false"
             class="absolute inset-x-0 top-full border-b border-line bg-surface">
            <div class="mx-auto max-w-[1280px] px-6 py-8">
                <div class="grid grid-cols-3 gap-6">
                    @foreach ($categories as $c)
                        <a href="{{ route('catalog.index', ['type' => $c['type']]) }}" class="group -m-3 flex gap-4 rounded-xl p-3 transition-colors hover:bg-ground">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-2" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $c['icon'] !!}</svg>
                            <span class="min-w-0">
                                <span class="block text-[15px] font-semibold text-ink">{{ $c['label'] }}</span>
                                <span class="mt-1 block text-sm leading-relaxed text-ink-2">{{ $c['text'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-8 flex items-center justify-end border-t border-line pt-5">
                    <a href="{{ route('catalog.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-accent-2 hover:text-ink">
                        Voir toute la boutique
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<x-layout.drawer />
