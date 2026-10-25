@props(['variant' => 'rail'])
@php
    // Même regroupement et mêmes routes que resources/js/Layouts/AdminLayout.jsx.
    $groups = [
        'Pilotage' => [
            ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z'],
        ],
        'Catalogue' => [
            ['label' => 'Produits', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'icon' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10'],
            ['label' => 'Offres MP', 'route' => 'admin.marketplace.offers.index', 'match' => 'admin.marketplace.offers.*', 'icon' => 'M20 12l-8 8-9-9V3h8zM7.5 7.5h.01'],
            ['label' => 'Catégories MP', 'route' => 'admin.marketplace.categories.index', 'match' => 'admin.marketplace.categories.*', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ],
        'Ventes' => [
            ['label' => 'Commandes', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5zM9 11h6'],
            ['label' => 'Commandes MP', 'route' => 'admin.marketplace.orders.index', 'match' => 'admin.marketplace.orders.*', 'icon' => 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5z'],
            ['label' => 'Calendrier', 'route' => 'admin.calendar.index', 'match' => 'admin.calendar.*', 'icon' => 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4M9 14h2M13 14h2M9 17h2'],
            ['label' => 'Coupons', 'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*', 'icon' => 'M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4zM9 8v8'],
        ],
        'Communauté' => [
            ['label' => 'Affiliés', 'route' => 'admin.affiliates.index', 'match' => 'admin.affiliates.*', 'icon' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3 2.7-5 6-5s6 2 6 5M16 3.5a3 3 0 0 1 0 6M21 20c0-2.5-1.8-4.4-4-4.9'],
            ['label' => 'Vendeurs', 'route' => 'admin.marketplace.vendors.index', 'match' => 'admin.marketplace.vendors.*', 'icon' => 'M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6'],
        ],
        'Finances' => [
            ['label' => 'Retraits', 'route' => 'admin.withdrawals.index', 'match' => 'admin.withdrawals.*', 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
            ['label' => 'Versements MP', 'route' => 'admin.marketplace.payouts.index', 'match' => 'admin.marketplace.payouts.*', 'icon' => 'M3 7h18v12H3zM3 11h18M7 15h3'],
            ['label' => 'Taux de commission', 'route' => 'admin.commission-campaigns.index', 'match' => 'admin.commission-campaigns.*', 'icon' => 'M19 5L5 19M7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM16.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z'],
        ],
        'Système' => [
            ['label' => 'Images de la vitrine', 'route' => 'admin.storefront-images.index', 'match' => 'admin.storefront-images.*', 'icon' => 'M4 5h16v14H4zM4 15l5-5 4 4 3-3 4 4M9 9.5h.01'],
            ['label' => 'Réglages Vitrine & MP', 'route' => 'admin.marketplace.settings.index', 'match' => 'admin.marketplace.settings.*', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2.1-1.2L14 3h-4l-.4 2.6a7 7 0 0 0-2.1 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2.1 1.2L10 21h4l.4-2.6a7 7 0 0 0 2.1-1.2l2.4 1 2-3.4-2-1.6c.06-.4.1-.8.1-1.2z'],
            ['label' => "Journal d'audit", 'route' => 'admin.audit-logs.index', 'match' => 'admin.audit-logs.*', 'icon' => 'M5 4h14v16l-4-2-3 2-3-2-4 2zM9 9h6M9 13h6'],
        ],
    ];
    $routeHas = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
@endphp

@php
    $list = function () use ($groups, $routeHas) {
        return $groups;
    };
@endphp

@if ($variant === 'rail')
<aside class="hidden w-60 shrink-0 lg:block">
    <nav aria-label="Navigation de la console" class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-6 overflow-y-auto pb-6">
@else
    <nav aria-label="Navigation de la console" class="space-y-6">
@endif
        @foreach ($groups as $title => $links)
            <div>
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.1em] text-night-ink-2">{{ $title }}</p>
                <ul class="space-y-0.5">
                    @foreach ($links as $l)
                        @php $active = $routeHas($l['route']) && request()->routeIs($l['match'] ?? $l['route']); @endphp
                        <li>
                            <a href="{{ $routeHas($l['route']) ? route($l['route']) : '#' }}"
                               @if ($active) aria-current="page" @endif
                               @class([
                                   'relative flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                   'bg-night-line font-semibold text-night-ink before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:rounded-full before:bg-accent' => $active,
                                   'text-night-ink-2 hover:bg-night-line/50 hover:text-night-ink' => ! $active,
                               ])>
                                <svg @class(['h-[18px] w-[18px] shrink-0', 'text-accent-2' => $active]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $l['icon'] }}"/></svg>
                                {{ $l['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
@if ($variant === 'rail')
</aside>
@endif
