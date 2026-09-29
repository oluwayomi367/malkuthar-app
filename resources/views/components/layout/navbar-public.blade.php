@php $cats = \App\Support\Catalog::categories(); @endphp

<header class="sticky top-0 z-40 border-b border-line-strong/70 bg-surface/98 backdrop-blur-md shadow-[0_2px_12px_-4px_rgba(46,44,40,0.06)]">
    <div class="relative mx-auto flex h-18 max-w-[1280px] items-center gap-4 px-4 sm:px-6 lg:gap-8"
         x-data="{ mega: false }"
         x-on:keydown.escape.window="mega = false">

        {{-- Burger (mobile) --}}
        <button type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/60 bg-white text-ink shadow-2xs transition-colors hover:bg-ground-alt lg:hidden"
                x-on:click="$store.drawer.toggle()"
                aria-label="Ouvrir le menu">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 6h14M3 10h14M3 14h14" stroke-linecap="round"/>
            </svg>
        </button>

        {{-- Logo & Maison Brand Mark --}}
        <a href="{{ url('/') }}" class="group flex shrink-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/70 bg-white p-1 shadow-2xs transition-all duration-200 group-hover:scale-105 group-hover:border-accent group-hover:shadow-xs">
                <img src="{{ asset('images/logo.png') }}" alt="Logo MALKUTHAR" class="h-full w-full object-contain">
            </div>
            <div class="flex flex-col">
                <span class="font-display text-[16px] font-extrabold tracking-[0.16em] text-ink leading-tight transition-colors group-hover:text-accent">MALKUTHAR</span>
                <span class="text-[9px] font-extrabold tracking-[0.24em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
            </div>
        </a>

        {{-- Menu desktop --}}
        <nav class="hidden items-center gap-1.5 lg:flex">
            @php
                $link = fn ($active) => 'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs uppercase tracking-[0.06em] transition-all duration-150 '
                    .($active ? 'bg-ground-alt/90 font-extrabold text-ink shadow-2xs ring-1 ring-line-strong/80' : 'font-semibold text-ink-2 hover:bg-ground-alt/60 hover:text-ink');
            @endphp

            <a href="{{ url('/') }}" class="{{ $link(request()->is('/')) }}">Accueil</a>

            <button type="button"
                    x-on:click="mega = !mega"
                    x-on:mouseenter="mega = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs uppercase tracking-[0.06em] transition-all duration-150 font-semibold text-ink-2 hover:bg-ground-alt/60 hover:text-ink"
                    :class="mega && 'bg-ground-alt/90 font-extrabold text-ink shadow-2xs ring-1 ring-line-strong/80'"
                    :aria-expanded="mega">
                <span>Produits</span>
                <svg class="h-3 w-3 transition-transform duration-200 text-ink-3" :class="mega && 'rotate-180 text-accent'"
                     viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <a href="{{ route('affiliation') }}" class="{{ $link(request()->routeIs('affiliation', 'affiliate.program-terms')) }}">Affiliation</a>
            @php $mpShop = \App\Support\MarketplaceSettings::enabled(); @endphp
            <a href="{{ $mpShop ? route('marketplace.shop') : route('marketplace') }}" class="{{ $link(request()->routeIs('marketplace', 'marketplace.shop*')) }}">
                <span>Marketplace</span>
                <span class="rounded-md bg-accent/15 px-1.5 py-0.5 text-[9px] font-extrabold tracking-wider text-accent uppercase">Place</span>
            </a>
            <a href="{{ route('about') }}" class="{{ $link(request()->routeIs('about')) }}">À propos</a>
            <a href="{{ route('contact') }}" class="{{ $link(request()->routeIs('contact')) }}">Contact</a>
        </nav>

        {{-- Droite : compte + panier --}}
        <div class="ml-auto flex items-center gap-2 sm:gap-2.5">
            @auth
                <a href="{{ url('/mon-compte') }}"
                   class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-line-strong/70 bg-white px-3.5 py-2 text-xs font-bold text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:shadow-xs">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent/15 text-[10px] font-black text-accent uppercase">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-line-strong/70 bg-white px-3.5 py-2 text-xs font-bold uppercase tracking-[0.06em] text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:text-accent hover:shadow-xs">
                    <svg class="h-3.5 w-3.5 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span>Connexion</span>
                </a>
            @endauth

            <a href="{{ route('cart') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-line-strong/70 bg-white text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:text-accent hover:shadow-xs" aria-label="Panier">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="8.5" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>
                </svg>
                <span x-data x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                      class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rust px-1 text-[10px] font-extrabold text-white ring-2 ring-white shadow-xs"></span>
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
            <div class="mx-auto max-w-[1280px] overflow-hidden rounded-2xl border border-line-strong/70 bg-surface shadow-[0_20px_48px_-16px_rgba(46,44,40,0.18)]">
                <div class="grid grid-cols-3 gap-3 p-5">
                    @foreach ($cats as $c)
                        <a href="{{ route('catalog.index', ['type' => $c['type']]) }}"
                           x-on:click="mega = false"
                           class="group flex gap-3.5 rounded-xl border border-line/60 bg-ground/40 p-4 transition-all duration-200 hover:border-accent/60 hover:bg-surface hover:shadow-xs">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line bg-white text-accent shadow-2xs group-hover:border-accent group-hover:bg-accent/10">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-display text-[15px] font-bold text-ink group-hover:text-accent transition-colors">{{ $c['label'] }}</span>
                                <span class="mt-1 block text-xs leading-relaxed text-ink-2">{{ $c['text'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="flex items-center justify-between border-t border-line bg-ground-alt/70 px-6 py-3.5 text-xs">
                    <span class="font-medium text-ink-3">Trois voies, une maison.</span>
                    <a href="{{ route('catalog.index') }}" x-on:click="mega = false" class="inline-flex items-center gap-1.5 font-bold text-accent hover:text-accent-2 transition-colors">
                        <span>Voir toute la boutique</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<x-layout.drawer />
