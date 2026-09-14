@props([
    'space' => 'affiliate',
])

@php
    $affiliateLinks = [
        ['label' => 'Tableau de bord', 'route' => 'affiliate.dashboard',           'icon' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z'],
        ['label' => 'Mes ventes',      'route' => 'affiliate.sales',                'icon' => 'M4 18V9M10 18V5M16 18v-6M2 21h18'],
        ['label' => 'Mes gains',       'route' => 'affiliate.earnings',             'icon' => 'M12 3v18M17 6.5C17 4.6 14.8 3.5 12 3.5S7 4.6 7 6.5s2 2.7 5 3.2 5 1.6 5 3.6-2.2 3.2-5 3.2-5-1.1-5-3'],
        ['label' => 'Ma lignée',       'route' => 'affiliate.lineage',              'icon' => 'M12 3a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM5 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM19 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM12 9v3M12 12l-7 3M12 12l7 3'],
        ['label' => 'Retraits',        'route' => 'affiliate.withdrawals.index', 'fallback' => 'affiliate.withdrawals.store', 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
        ['label' => 'Moyen de paiement','route' => 'affiliate.withdrawal-method.store', 'icon' => 'M3 7h18v10H3zM3 11h18M7 15h4'],
        ['label' => 'Conditions',      'route' => 'affiliate.program-terms',        'icon' => 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h6'],
    ];
    $accountLinks = [
        ['label' => 'Tableau de bord', 'url' => '/mon-compte',                'icon' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z'],
        ['label' => 'Téléchargements', 'url' => '/mon-compte/telechargements', 'icon' => 'M12 3v10m0 0l-4-4m4 4l4-4M4 21h16'],
        ['label' => 'Commandes',       'url' => '/mon-compte/commandes',       'icon' => 'M5 4h14l-1 4H6zM4 8h16l-1.2 11.2a1 1 0 0 1-1 .8H6.2a1 1 0 0 1-1-.8z'],
        ['label' => 'Rendez-vous',     'url' => '/mon-compte/rendez-vous',     'icon' => 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4'],
        ['label' => 'Profil',          'url' => '/mon-compte/profil',          'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM5 20c0-3.3 3.1-6 7-6s7 2.7 7 6'],
    ];

    $vendorLinks = [
        ['label' => 'Tableau de bord', 'route' => 'vendor.dashboard',  'icon' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z'],
        ['label' => 'Mes offres',      'route' => 'vendor.offers.index', 'fallback' => 'vendor.offers.create', 'icon' => 'M5 4h14l-1 4H6zM4 8h16l-1.2 11.2a1 1 0 0 1-1 .8H6.2a1 1 0 0 1-1-.8z'],
        ['label' => 'Mes commandes',   'route' => 'vendor.orders.index', 'icon' => 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4'],
        ['label' => 'Mes revenus',     'route' => 'vendor.settlement.index', 'icon' => 'M12 3v18M17 6.5C17 4.6 14.8 3.5 12 3.5S7 4.6 7 6.5s2 2.7 5 3.2 5 1.6 5 3.6-2.2 3.2-5 3.2-5-1.1-5-3'],
        ['label' => 'Mes versements',  'route' => 'vendor.payouts.index', 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
        ['label' => 'Ma boutique',     'route' => 'vendor.profile.edit', 'icon' => 'M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6'],
    ];

    $links = match ($space) {
        'account' => $accountLinks,
        'vendor' => $vendorLinks,
        default => $affiliateLinks,
    };
    $spaceTitle = match ($space) {
        'account' => 'Espace client',
        'vendor' => 'Espace vendeur',
        default => 'Espace affilié',
    };

    $resolve = function (array $link) {
        $href = isset($link['url'])
            ? url($link['url'])
            : (\Illuminate\Support\Facades\Route::has($link['route']) ? route($link['route']) : '#');

        $active = isset($link['url'])
            ? (trim($link['url'], '/') === trim(request()->path(), '/'))
            : (request()->routeIs($link['route']) || (isset($link['fallback']) && request()->routeIs($link['fallback'])));

        return [$href, $active];
    };
@endphp

{{-- Rail desktop --}}
<aside class="hidden w-60 shrink-0 lg:block">
    <div class="sticky top-24">
        <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">{{ $spaceTitle }}</p>
        <nav class="flex flex-col gap-0.5 text-sm">
            @foreach ($links as $link)
                @php [$href, $active] = $resolve($link); @endphp
                <a href="{{ $href }}" @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors',
                    'bg-black/[0.05] font-semibold text-ink' => $active,
                    'text-ink-2 hover:bg-surface hover:text-ink' => ! $active,
                ]) @if ($active) aria-current="page" @endif>
                    <svg @class(['h-4 w-4 shrink-0', 'text-accent' => $active, 'text-ink-3' => ! $active])
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</aside>

{{-- Sous-navigation mobile : barre de puces défilante --}}
<nav class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:hidden" aria-label="{{ $spaceTitle }}">
    @foreach ($links as $link)
        @php [$href, $active] = $resolve($link); @endphp
        <a href="{{ $href }}" @class([
            'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[13px]',
            'border-accent bg-accent/10 font-semibold text-accent' => $active,
            'border-black/10 text-ink-2' => ! $active,
        ]) @if ($active) aria-current="page" @endif>
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="{{ $link['icon'] }}"/>
            </svg>
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
