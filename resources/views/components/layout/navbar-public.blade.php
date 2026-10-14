@php
    $mpEnabled = \App\Support\MarketplaceSettings::enabled();
    $links = [
        ['Boutique', url('/boutique'), request()->is('boutique*', 'produits*')],
        ['Place de marché', $mpEnabled ? route('marketplace.shop') : route('marketplace'), request()->routeIs('marketplace', 'marketplace.shop*', 'marketplace.cart*', 'marketplace.checkout*')],
        ['Affiliation', route('affiliation'), request()->routeIs('affiliation', 'affiliate.program-terms')],
        ['À propos', route('about'), request()->routeIs('about')],
        ['Contact', route('contact'), request()->routeIs('contact')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-line bg-ground">
    <div class="mx-auto flex h-[72px] max-w-[1280px] items-center gap-3 px-4 sm:px-6 lg:gap-10">

        {{-- Menu (mobile) --}}
        <button type="button"
                class="-ml-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-ink transition-colors hover:bg-ground-alt lg:hidden"
                x-data
                x-on:click="$store.drawer.toggle()"
                aria-label="Ouvrir le menu">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M3 6h14M3 10h14M3 14h14" stroke-linecap="round"/>
            </svg>
        </button>

        {{-- Marque --}}
        <a href="{{ url('/') }}" class="flex min-w-0 shrink-0 items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 shrink-0 object-contain">
            <span class="font-display text-[20px] font-semibold tracking-[0.18em] text-ink sm:text-[22px]">MALKUTHAR</span>
        </a>

        {{-- Navigation (desktop) --}}
        <nav class="hidden items-center gap-7 lg:flex" aria-label="Navigation principale">
            @foreach ($links as [$label, $href, $active])
                <a href="{{ $href }}"
                   @if ($active) aria-current="page" @endif
                   class="relative py-2 text-sm font-medium transition-colors {{ $active ? 'text-ink after:absolute after:inset-x-0 after:-bottom-px after:h-px after:bg-accent' : 'text-ink-2 hover:text-ink' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- Compte + panier --}}
        <div class="ml-auto flex items-center gap-1 sm:gap-2">
            @auth
                <a href="{{ url('/espace') }}"
                   class="hidden min-h-11 items-center gap-2 rounded-full border border-line-strong px-4 text-sm font-medium text-ink transition-colors hover:border-ink sm:inline-flex">
                    <svg class="h-4 w-4 text-ink-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" /><circle cx="12" cy="7" r="4" />
                    </svg>
                    Mon espace
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="hidden min-h-11 items-center gap-2 rounded-full border border-line-strong px-4 text-sm font-medium text-ink transition-colors hover:border-ink sm:inline-flex">
                    <svg class="h-4 w-4 text-ink-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" /><circle cx="12" cy="7" r="4" />
                    </svg>
                    Connexion
                </a>
            @endauth

            <a href="{{ route('cart') }}" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full text-ink transition-colors hover:bg-ground-alt" aria-label="Panier">
                <svg class="h-[22px] w-[22px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="8.5" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>
                </svg>
                <span x-data x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                      class="absolute right-0.5 top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-xs font-semibold text-white"></span>
            </a>
        </div>
    </div>
</header>

<x-layout.drawer />
