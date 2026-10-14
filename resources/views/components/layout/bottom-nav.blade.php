@php
    use App\Support\SpaceNavigation;

    $space = SpaceNavigation::current();
    $tabs = array_values(array_filter(SpaceNavigation::links($space), fn ($l) => $l['mobile']));
    $tabs = array_slice($tabs, 0, 4);
    $tabs[] = [
        'label' => 'Boutique',
        'href' => url('/boutique'),
        'active' => false,
        'icon' => SpaceNavigation::ICONS['store'],
    ];

    $activeText = [
        'account' => 'text-space-client',
        'affiliate' => 'text-space-affiliate',
        'vendor' => 'text-space-vendor',
    ][$space] ?? 'text-space-client';
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 backdrop-blur lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom);" aria-label="{{ SpaceNavigation::title($space) }}">
    <div class="mx-auto grid max-w-lg grid-cols-5">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}" @class([
                'flex min-h-14 flex-col items-center justify-center gap-1 text-[11px] font-semibold transition-colors',
                $activeText => $tab['active'],
                'text-ink-3' => ! $tab['active'],
            ]) @if ($tab['active']) aria-current="page" @endif>
                <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $tab['icon'] }}"/>
                </svg>
                <span class="max-w-full truncate px-1">{{ $tab['label'] === 'Tableau de bord' ? 'Accueil' : $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
<div class="h-20 lg:hidden" aria-hidden="true"></div>
