@extends('layouts.app')

@section('title', 'Mes téléchargements')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Ma bibliothèque</h1>
    <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-ink-2">Vos produits digitaux. Chaque lien reste valable 7 jours ; rechargez cette page pour en obtenir un nouveau.</p>
</header>

@if ($items->isEmpty())
    <x-ui.empty-state
        class="mt-8"
        icon="download"
        title="Aucun produit digital"
        text="Vos ebooks, formations et fichiers achetés apparaîtront ici.">
        <x-slot:cta>
            <x-ui.button :href="route('catalog.index', ['type' => 'digital'])" variant="primary">Explorer la boutique</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <ul class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($items as $item)
            @php $exhausted = $item->max_downloads > 0 && (int) $item->download_count >= $item->max_downloads; @endphp
            <li class="flex flex-col rounded-2xl border border-line bg-surface p-5">
                <p class="text-base font-semibold leading-snug text-ink">{{ $item->product_name }}</p>
                <p class="mt-1.5 text-[13px] leading-relaxed text-ink-3">
                    Commande <span class="font-mono">{{ $item->order?->order_number }}</span>
                    · {{ $item->order?->created_at?->format('d/m/Y') }}
                </p>
                @if ($item->max_downloads > 0)
                    <p @class(['mt-1 text-[13px]', 'font-semibold text-rust' => $exhausted, 'text-ink-2' => ! $exhausted])>
                        {{ (int) $item->download_count }}/{{ $item->max_downloads }} téléchargements
                    </p>
                @endif
                <div class="mt-auto pt-4">
                    @if ($exhausted)
                        <p class="text-sm text-ink-2">Quota atteint. Contactez le support si vous avez besoin d'un nouveau lien.</p>
                    @else
                        <x-ui.button :href="$item->download_url" variant="secondary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7.5 10.5L12 15l4.5-4.5M5 19h14"/></svg>
                            Télécharger
                        </x-ui.button>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
@endsection
