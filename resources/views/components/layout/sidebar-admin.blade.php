@php
    $links = [
        ['label' => 'Tableau de bord', 'route' => 'admin.dashboard',      'icon' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z'],
        ['label' => 'Produits',        'route' => 'admin.products.index',  'icon' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10'],
        ['label' => 'Commandes',       'route' => 'admin.orders.index',    'icon' => 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5zM9 11h6'],
        ['label' => 'Calendrier',      'route' => 'admin.calendar.index',  'icon' => 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4M9 14h2M13 14h2M9 17h2'],
        ['label' => 'Affiliés',        'route' => 'admin.affiliates.index','icon' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3 2.7-5 6-5s6 2 6 5M16 3.5a3 3 0 0 1 0 6M21 20c0-2.5-1.8-4.4-4-4.9'],
        ['label' => 'Vendeurs',        'route' => 'admin.marketplace.vendors.index', 'match' => 'admin.marketplace.vendors.*', 'icon' => 'M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6'],
        ['label' => 'Catégories MP',   'route' => 'admin.marketplace.categories.index', 'match' => 'admin.marketplace.categories.*', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ['label' => 'Offres MP',       'route' => 'admin.marketplace.offers.index', 'match' => 'admin.marketplace.offers.*', 'icon' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7'],
        ['label' => 'Commandes MP',    'route' => 'admin.marketplace.orders.index', 'match' => 'admin.marketplace.orders.*', 'icon' => 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5z'],
        ['label' => 'Versements MP',   'route' => 'admin.marketplace.payouts.index', 'match' => 'admin.marketplace.payouts.*', 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
        ['label' => 'Retraits',        'route' => 'admin.withdrawals.index','icon' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
        ['label' => 'Taux de commission','route' => 'admin.commission-campaigns.index','icon' => 'M12 3v18M17 6.5C17 4.6 14.8 3.5 12 3.5S7 4.6 7 6.5s2 2.7 5 3.2 5 1.6 5 3.6-2.2 3.2-5 3.2-5-1.1-5-3'],
        ['label' => 'Coupons',         'route' => 'admin.coupons.index',   'icon' => 'M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4zM9 8v8'],
        ['label' => "Journal d'audit", 'route' => 'admin.audit-logs.index','icon' => 'M5 4h14v16l-4-2-3 2-3-2-4 2zM9 9h6M9 13h6'],
    ];
@endphp

{{-- Rail desktop --}}
<aside class="hidden w-56 shrink-0 lg:block">
    <nav class="sticky top-20 flex flex-col gap-0.5 text-sm">
        @foreach ($links as $l)
            @php $active = \Illuminate\Support\Facades\Route::has($l['route']) && request()->routeIs($l['match'] ?? $l['route']); @endphp
            <a href="{{ \Illuminate\Support\Facades\Route::has($l['route']) ? route($l['route']) : '#' }}"
               @class([
                   'flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-colors',
                   'bg-black/[0.05] font-semibold text-ink' => $active,
                   'text-ink-2 hover:bg-surface hover:text-ink' => ! $active,
               ])>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $l['icon'] }}"/></svg>
                {{ $l['label'] }}
            </a>
        @endforeach
    </nav>
</aside>

{{-- Chips mobile --}}
<nav class="mb-6 flex gap-2 overflow-x-auto lg:hidden">
    @foreach ($links as $l)
        @php $active = \Illuminate\Support\Facades\Route::has($l['route']) && request()->routeIs($l['match'] ?? $l['route']); @endphp
        <a href="{{ \Illuminate\Support\Facades\Route::has($l['route']) ? route($l['route']) : '#' }}"
           @class([
               'shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-sm',
               'border-accent bg-accent/10 font-semibold text-accent' => $active,
               'border-black/10 text-ink-2' => ! $active,
           ])>{{ $l['label'] }}</a>
    @endforeach
</nav>
