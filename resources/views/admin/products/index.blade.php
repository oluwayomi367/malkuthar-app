@extends('layouts.admin')

@section('title', 'Administration — Produits')
@section('eyebrow', 'Catalogue')
@section('heading', 'Produits')

@section('actions')
    <x-ui.button :href="route('admin.products.create')" variant="primary">+ Nouveau produit</x-ui.button>
@endsection

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
    $statusMeta = [
        'published' => ['Publié', 'accent'],
        'draft' => ['Brouillon', 'muted'],
        'archived' => ['Archivé', 'rust'],
    ];
@endphp

@if ($lowStock->isNotEmpty())
    <div class="mb-6 rounded-2xl border border-amber/30 bg-amber/[0.07] p-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-amber">Stock faible</p>
        <ul class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-ink-2">
            @foreach ($lowStock as $p)
                <li>
                    <a href="{{ route('admin.products.edit', $p) }}" class="hover:text-ink">
                        {{ $p->name }} — <span class="font-semibold text-amber">{{ (int) $p->stock_available }}</span> restant(s)
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
    <div class="min-w-[200px] flex-1">
        <x-ui.field name="q" label="Recherche" :value="$q" placeholder="Nom, SKU, slug…" />
    </div>
    <x-ui.select name="type" label="Type" :selected="$type" placeholder="Tous les types"
        :options="['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service']" />
    <x-ui.select name="status" label="Statut" :selected="$status" placeholder="Tous les statuts"
        :options="['published' => 'Publié', 'draft' => 'Brouillon', 'archived' => 'Archivé']" />
    <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>
    @if ($q !== '' || $type || $status)
        <a href="{{ route('admin.products.index') }}" class="pb-2.5 text-sm text-ink-3 hover:text-ink">Réinitialiser</a>
    @endif
</form>

@if ($products->isEmpty())
    <x-ui.empty-state title="Aucun produit" text="Ajustez les filtres ou créez un premier produit.">
        <x-slot:cta>
            <x-ui.button :href="route('admin.products.create')" variant="primary">+ Nouveau produit</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <div class="overflow-x-auto rounded-2xl border border-black/10">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                <tr>
                    <th class="px-4 py-3 font-semibold">Produit</th>
                    <th class="px-4 py-3 font-semibold">Type</th>
                    <th class="px-4 py-3 font-semibold">Prix</th>
                    <th class="px-4 py-3 font-semibold">Stock</th>
                    <th class="px-4 py-3 font-semibold">Ventes</th>
                    <th class="px-4 py-3 font-semibold">Commissions</th>
                    <th class="px-4 py-3 font-semibold">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @foreach ($products as $p)
                    <tr class="hover:bg-surface">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-black/10 bg-ground/60">
                                    @if ($p->main_image)
                                        <img src="{{ $p->main_image }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <a href="{{ route('admin.products.show', $p) }}" class="block truncate font-medium text-ink hover:text-accent">{{ $p->name }}</a>
                                    <span class="block truncate text-xs text-ink-3">{{ $p->sku ?: $p->slug }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge tone="muted">{{ $typeLabels[$p->type] ?? $p->type }}</x-ui.badge>
                            @if ($p->is_affiliable)<x-ui.badge tone="accent" class="ml-1">Affiliable</x-ui.badge>@endif
                        </td>
                        <td class="px-4 py-3 text-ink-2">
                            {{ $m($p->promotional_price ?: $p->price) }} {{ $p->currency }}
                            @if ($p->promotional_price)<span class="block text-xs text-ink-3 line-through">{{ $m($p->price) }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($p->type === 'physical')
                                <span @class(['font-semibold', 'text-rust' => (int) $p->stock_available <= 5, 'text-ink' => (int) $p->stock_available > 5])>{{ (int) $p->stock_available }}</span>
                                <span class="text-xs text-ink-3"> dispo · {{ (int) $p->stock_reserved }} rés.</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        @php $r = $reports[$p->id] ?? null; @endphp
                        <td class="px-4 py-3">
                            @if ($r && $r['units_sold'] > 0)
                                <span class="font-semibold text-ink">{{ $r['units_sold'] }}</span>
                                <span class="block text-xs text-ink-3">{{ $m($r['revenue']) }} {{ $p->currency }}</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($p->is_affiliable || ($r && $r['commission_net'] != 0))
                                <span class="text-ink-2">{{ $m($r['commission_net'] ?? 0) }} {{ $p->currency }}</span>
                            @else
                                <span class="text-ink-3" title="Produit non affiliable">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php [$sLabel, $sTone] = $statusMeta[$p->status] ?? [$p->status, 'muted']; @endphp
                            <x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.products.edit', $p) }}" class="text-xs text-accent hover:underline">Modifier</a>
                            <form method="POST" action="{{ route('admin.products.delete', $p) }}" class="ml-3 inline"
                                  onsubmit="return confirm('Supprimer « {{ $p->name }} » ?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rust hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$products" />
@endif
@endsection
