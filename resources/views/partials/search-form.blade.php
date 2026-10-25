{{-- Recherche de l'en-tête (miroir de SearchForm dans StorefrontLayout.jsx). --}}
<form role="search" method="GET" action="{{ route('catalog.index') }}" class="flex items-stretch {{ $searchClass ?? '' }}">
    <label for="{{ $searchId }}" class="sr-only">Rechercher un produit</label>
    <input id="{{ $searchId }}" type="search" name="q" value="{{ request()->is('boutique') ? request('q') : '' }}"
           placeholder="Rechercher un produit, un soin…"
           class="min-h-11 w-full min-w-0 rounded-l-full border border-r-0 border-line-strong bg-surface px-5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none">
    <button type="submit" aria-label="Lancer la recherche"
            class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-r-full bg-accent px-5 text-sm font-semibold text-white transition-colors hover:bg-accent-2">
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
            <path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z"/><path d="M20 20l-3.5-3.5"/>
        </svg>
        <span class="hidden md:inline">Rechercher</span>
    </button>
</form>
