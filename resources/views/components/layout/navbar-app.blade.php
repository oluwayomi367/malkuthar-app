@php
    use App\Support\SpaceNavigation;

    $user = Auth::user();
    $initial = strtoupper(mb_substr($user?->first_name ?: 'M', 0, 1));
    $current = SpaceNavigation::current();
    $spaces = SpaceNavigation::switcher($user);

    // Classes littérales (lues par Tailwind) — teinte de repère par espace.
    $solid = [
        'account' => 'bg-space-client text-white',
        'affiliate' => 'bg-space-affiliate text-white',
        'vendor' => 'bg-space-vendor text-white',
    ];
    $dot = [
        'account' => 'bg-space-client',
        'affiliate' => 'bg-space-affiliate',
        'vendor' => 'bg-space-vendor',
    ];
@endphp

<a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Aller au contenu</a>

<header class="sticky top-0 z-40 border-b border-line bg-surface/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-[1320px] items-center gap-4 px-4 sm:px-6 lg:gap-6">
        <a href="{{ url('/') }}" class="inline-flex shrink-0 items-center gap-2.5 text-ink">
            <img src="{{ \App\Support\StorefrontImages::url('brand.logo') }}" alt="" class="h-8 w-8 object-contain">
            <span class="font-display text-[22px] font-semibold tracking-[0.14em]">MALKUTHAR</span>
        </a>

        @if (count($spaces) > 1)
            <nav aria-label="Changer d'espace" class="hidden items-center gap-1 rounded-full border border-line bg-ground p-1 md:flex">
                @foreach ($spaces as $item)
                    @php $isCurrent = $item['space'] === $current; @endphp
                    <a href="{{ $item['href'] }}" @if ($isCurrent) aria-current="page" @endif
                       @class([
                           'inline-flex h-9 items-center gap-2 rounded-full px-3.5 text-[13px] font-semibold transition-colors',
                           $solid[$item['space']] => $isCurrent,
                           'text-ink-2 hover:bg-surface hover:text-ink' => ! $isCurrent,
                       ])>
                        <span @class(['h-2 w-2 rounded-full', 'bg-white' => $isCurrent, $dot[$item['space']] => ! $isCurrent])></span>
                        {{ $item['label'] }}
                        @unless ($item['enabled'])
                            <span class="text-[11px] font-medium text-ink-3">· activer</span>
                        @endunless
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2" x-data="{ open: false }">
            <a href="{{ url('/boutique') }}" class="hidden h-11 items-center rounded-full px-4 text-sm font-semibold text-ink-2 hover:bg-ground hover:text-ink sm:inline-flex">
                Boutique
            </a>

            <a href="{{ route('cart') }}" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-line bg-surface text-ink hover:border-line-strong" aria-label="Panier">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ SpaceNavigation::ICONS['bag'] }}"/>
                </svg>
                <span x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                      class="absolute -right-0.5 -top-0.5 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-accent px-1 text-[11px] font-bold text-white"></span>
            </a>

            <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" aria-haspopup="true"
                    aria-label="Menu du compte"
                    class="inline-flex h-11 items-center gap-1.5 rounded-full py-1 pl-1 pr-2 text-sm text-ink hover:bg-ground">
                <span @class(['inline-flex h-9 w-9 items-center justify-center rounded-full text-[13px] font-bold', $solid[$current]])>{{ $initial }}</span>
                <svg class="h-4 w-4 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="open" x-cloak x-on:click.outside="open = false" x-on:keydown.escape.window="open = false"
                 x-transition.origin.top.right
                 class="absolute right-4 top-[60px] w-64 rounded-2xl border border-line bg-surface py-2 text-sm shadow-[0_18px_40px_-18px_rgba(28,25,23,0.25)] sm:right-6">
                <p class="truncate px-4 pb-2 pt-1 text-xs text-ink-3">{{ $user?->email }}</p>

                <p class="px-4 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Mes espaces</p>
                @foreach ($spaces as $item)
                    <a href="{{ $item['href'] }}" class="flex items-center gap-3 px-4 py-2.5 text-ink hover:bg-ground">
                        <span @class(['h-2 w-2 rounded-full', $dot[$item['space']]])></span>
                        <span class="flex-1">{{ $item['enabled'] ? SpaceNavigation::title($item['space']) : 'Devenir affilié' }}</span>
                        @if ($item['space'] === $current)<span class="text-xs text-ink-3">actuel</span>@endif
                    </a>
                @endforeach
                @unless (collect($spaces)->contains('space', 'vendor'))
                    @if (Route::has('marketplace.become-vendor'))
                        <a href="{{ route('marketplace.become-vendor') }}" class="flex items-center gap-3 px-4 py-2.5 text-ink hover:bg-ground">
                            <span class="h-2 w-2 rounded-full bg-space-vendor"></span>
                            <span class="flex-1">Devenir vendeur</span>
                        </a>
                    @endif
                @endunless
                @if ($user?->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-ink hover:bg-ground">
                        <span class="h-2 w-2 rounded-full bg-night"></span>
                        <span class="flex-1">Administration</span>
                    </a>
                @endif

                <div class="my-2 border-t border-line"></div>
                <a href="{{ route('account.profile') }}" class="block px-4 py-2.5 text-ink hover:bg-ground">Profil et sécurité</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block w-full px-4 py-2.5 text-left text-ink hover:bg-ground">Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>
