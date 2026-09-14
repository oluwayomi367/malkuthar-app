@php
    $user = Auth::user();
    $espaceUrl = $user?->isAffiliate() ? route('affiliate.dashboard') : url('/mon-compte');
    $isEspace = request()->is('affiliate*') || request()->is('mon-compte*');

    $tabs = [
        ['label' => 'Accueil',  'url' => url('/'),        'active' => request()->is('/'),           'icon' => 'M3 9l7-6 7 6v8a1 1 0 0 1-1 1h-4v-5H8v5H4a1 1 0 0 1-1-1z'],
        ['label' => 'Boutique', 'url' => url('/boutique'),'active' => request()->is('boutique*') || request()->is('produits*'), 'icon' => 'M4 6h12l-1 10a1 1 0 0 1-1 .9H6a1 1 0 0 1-1-.9zM7 6V5a3 3 0 0 1 6 0v1'],
        ['label' => 'Espace',   'url' => $espaceUrl,      'active' => $isEspace,                    'icon' => 'M3 10l7-7 7 7M5 8v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V8'],
        ['label' => 'Compte',   'url' => url('/mon-compte/profil'), 'active' => request()->is('mon-compte/profil'), 'icon' => 'M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM4 17c0-3 2.7-5 6-5s6 2 6 5'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-black/[0.06] bg-ground/80 backdrop-blur-xl lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="mx-auto grid max-w-md grid-cols-4">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class([
                'flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition-colors',
                'text-ink' => $tab['active'],
                'text-ink-3' => ! $tab['active'],
            ])>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="{{ $tab['icon'] }}" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $tab['label'] }}</span>
                <span @class([
                    'h-0.5 w-4 rounded-full',
                    'bg-accent' => $tab['active'],
                    'bg-transparent' => ! $tab['active'],
                ])></span>
            </a>
        @endforeach
    </div>
</nav>
<div class="h-20 lg:hidden" aria-hidden="true"></div>
