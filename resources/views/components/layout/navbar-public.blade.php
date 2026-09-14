@php $cats = \App\Support\Catalog::categories(); @endphp

<header class="sticky top-0 z-40 border-b border-line bg-ground/85 backdrop-blur-xl">
    <div class="relative mx-auto flex h-16 max-w-[1180px] items-center gap-4 px-4 sm:px-6 lg:gap-6"
         x-data="{ mega: false }"
         x-on:keydown.escape.window="mega = false">

        {{-- Burger (mobile) --}}
        <button type="button"
                class="-ml-2 inline-flex h-11 w-11 items-center justify-center text-ink lg:hidden"
                x-on:click="$store.drawer.toggle()"
                aria-label="Ouvrir le menu">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M3 6h14M3 10h14M3 14h14" stroke-linecap="round"/>
            </svg>
        </button>

        {{-- Logo — collé à gauche --}}
        <a href="{{ url('/') }}" class="font-display text-[15px] font-bold tracking-[0.16em] text-ink">MALKUTHAR</a>

        {{-- Menu desktop --}}
        <nav class="hidden items-center gap-0.5 text-sm lg:flex">
            @php
                $link = fn ($active) => 'rounded-lg px-3 py-2 transition-colors '
                    .($active ? 'font-medium text-ink' : 'text-ink-2 hover:text-ink');
            @endphp

            <a href="{{ url('/') }}" class="{{ $link(request()->is('/')) }}">Accueil</a>

            <button type="button"
                    x-on:click="mega = !mega"
                    x-on:mouseenter="mega = true"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-ink-2 transition-colors hover:text-ink"
                    :class="mega && 'text-ink'"
                    :aria-expanded="mega">
                Produits
                <svg class="h-3.5 w-3.5 transition-transform" :class="mega && 'rotate-180'"
                     viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <a href="{{ route('affiliation') }}" class="{{ $link(request()->routeIs('affiliation', 'affiliate.program-terms')) }}">Affiliation</a>
            @php $mpShop = \App\Support\MarketplaceSettings::enabled(); @endphp
            <a href="{{ $mpShop ? route('marketplace.shop') : route('marketplace') }}" class="{{ $link(request()->routeIs('marketplace', 'marketplace.shop*')) }}">Marketplace</a>
            <a href="{{ route('about') }}" class="{{ $link(request()->routeIs('about')) }}">À propos</a>
            <a href="{{ route('contact') }}" class="{{ $link(request()->routeIs('contact')) }}">Contact</a>
        </nav>

        {{-- Droite : compte + panier --}}
        <div class="ml-auto flex items-center gap-1">
            <a href="{{ Auth::check() ? url('/mon-compte') : route('login') }}"
               class="hidden h-11 items-center px-3 text-sm text-ink-2 transition-colors hover:text-ink sm:inline-flex">
                {{ Auth::check() ? 'Mon compte' : 'Connexion' }}
            </a>
            <a href="{{ route('cart') }}" class="relative inline-flex h-11 w-11 items-center justify-center text-ink" aria-label="Panier">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="8.5" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>
                </svg>
                <span x-data x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                      class="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white"></span>
            </a>
        </div>

        {{-- ================= MÉGA-MENU « Produits » ================= --}}
        <div x-show="mega" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-on:mouseenter="mega = true"
             x-on:mouseleave="mega = false"
             x-on:click.outside="mega = false"
             class="absolute inset-x-0 top-full hidden px-4 pt-2 sm:px-6 lg:block">
            <div class="mx-auto max-w-[1180px] overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_16px_44px_-24px_rgba(46,44,40,0.35)]">
                <div class="grid grid-cols-3 gap-2 p-4">
                    @foreach ($cats as $c)
                        <a href="{{ route('catalog.index', ['type' => $c['type']]) }}"
                           x-on:click="mega = false"
                           class="group flex gap-3 rounded-xl border border-transparent p-3 transition-colors hover:border-line hover:bg-ground-alt">
                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ground-alt text-accent">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-display text-[15px] font-semibold text-ink">{{ $c['label'] }}</span>
                                <span class="mt-1 block text-[12.5px] leading-snug text-ink-2">{{ $c['text'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="flex items-center justify-between border-t border-line bg-ground-alt/60 px-5 py-3 text-sm">
                    <span class="text-ink-3">Trois voies, une maison.</span>
                    <a href="{{ route('catalog.index') }}" x-on:click="mega = false" class="font-semibold text-accent hover:underline">Voir toute la boutique →</a>
                </div>
            </div>
        </div>
    </div>
</header>

<x-layout.drawer />
