@php
    $user = Auth::user();
    $initial = strtoupper(mb_substr($user?->first_name ?: 'M', 0, 1));
@endphp

<header class="sticky top-0 z-40 border-b border-black/[0.06] bg-ground/70 backdrop-blur-xl">
    <div class="mx-auto flex h-14 max-w-[1180px] items-center justify-between px-4 sm:px-6">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 font-display text-[15.5px] font-bold tracking-[0.18em] text-ink">
            <img src="{{ asset('images/logo.png') }}" alt="Logo MALKUTHAR" class="h-8 w-8 object-contain">
            <span>MALKUTHAR</span>
        </a>

        <div class="flex items-center gap-2" x-data="{ open: false }">
            <a href="{{ route('cart') }}" class="relative inline-flex h-11 w-11 items-center justify-center text-ink" aria-label="Panier">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="8.5" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>
                </svg>
                <span x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                      class="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white"></span>
            </a>

            <button type="button" x-on:click="open = !open"
                    class="inline-flex items-center gap-1.5 rounded-full py-1 pl-1 pr-2 text-sm text-ink hover:bg-black/[0.04]">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-accent text-xs font-semibold text-white">{{ $initial }}</span>
                <svg class="h-4 w-4 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="open" x-cloak x-on:click.outside="open = false"
                 x-transition.origin.top.right
                 class="absolute right-4 top-14 w-56 rounded-2xl border border-black/10 bg-ground/95 py-1 text-sm backdrop-blur-xl sm:right-6">
                <p class="truncate px-3 py-2 text-xs text-ink-3">{{ $user?->email }}</p>
                <a href="{{ url('/mon-compte') }}" class="block px-3 py-2.5 text-ink hover:bg-black/[0.04]">Espace client</a>
                @if ($user?->isAffiliate())
                    <a href="{{ route('affiliate.dashboard') }}" class="block px-3 py-2.5 text-ink hover:bg-black/[0.04]">Espace affilié</a>
                @else
                    <a href="{{ route('affiliate.join') }}" class="block px-3 py-2.5 text-ink hover:bg-black/[0.04]">Devenir affilié</a>
                @endif
                <a href="{{ url('/mon-compte/profil') }}" class="block px-3 py-2.5 text-ink hover:bg-black/[0.04]">Profil</a>
                <div class="my-1 border-t border-black/[0.06]"></div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block w-full px-3 py-2.5 text-left text-ink hover:bg-black/[0.04]">Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>
