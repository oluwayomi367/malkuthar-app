@extends('layouts.app')

@section('title', 'Mes téléchargements')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes téléchargements</h1>
<p class="mt-1 text-sm text-ink-2">Vos produits digitaux. Chaque lien reste valable 7 jours ; régénérez-le en rechargeant cette page.</p>

<div class="mt-8 space-y-2">
    @forelse ($items as $item)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-black/10 bg-surface px-4 py-4">
            <div class="min-w-0">
                <p class="truncate text-[15px] font-medium text-ink">{{ $item->product_name }}</p>
                <p class="mt-0.5 text-xs text-ink-3">
                    Commande <span class="font-mono">{{ $item->order?->order_number }}</span>
                    · {{ $item->order?->created_at?->format('d/m/Y') }}
                    @if ($item->max_downloads > 0)
                        · <span class="@if ((int) $item->download_count >= $item->max_downloads) text-rust @endif">{{ (int) $item->download_count }}/{{ $item->max_downloads }} téléchargements</span>
                    @endif
                </p>
            </div>
            @if ($item->max_downloads > 0 && (int) $item->download_count >= $item->max_downloads)
                <span class="inline-flex h-9 items-center rounded-lg border border-black/10 px-3.5 text-sm text-ink-3">Quota atteint</span>
            @else
                <a href="{{ $item->download_url }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3.5 text-sm font-semibold text-white shadow-[0_8px_24px_-8px] shadow-black/10">
                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 2v8m0 0L5 7m3 3l3-3M3 13h10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Télécharger
                </a>
            @endif
        </div>
    @empty
        <x-ui.empty-state
            title="Aucun produit digital"
            text="Vos ebooks, formations et fichiers achetés apparaîtront ici.">
            <x-slot:cta>
                <x-ui.button :href="route('catalog.index', ['type' => 'digital'])" variant="primary">Explorer la boutique</x-ui.button>
            </x-slot:cta>
        </x-ui.empty-state>
    @endforelse
</div>
@endsection
