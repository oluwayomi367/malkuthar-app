{{-- Filtres boutique : onglets nature (liens) + catégorie / tri (form GET auto-submit). --}}
@php
    $natureItems = [
        route('catalog.index', array_filter(['category' => $category, 'sort' => $sort !== 'recent' ? $sort : null])) => 'Tout',
    ];
    foreach (['digital' => 'Digitaux', 'physical' => 'Physiques', 'service' => 'Services'] as $k => $lbl) {
        $natureItems[route('catalog.index', array_filter(['type' => $k, 'category' => $category, 'sort' => $sort !== 'recent' ? $sort : null]))] = $lbl;
    }
    $activeNature = route('catalog.index', array_filter([
        'type' => in_array($type, ['digital', 'physical', 'service'], true) ? $type : null,
        'category' => $category,
        'sort' => $sort !== 'recent' ? $sort : null,
    ]));
@endphp

<x-ui.tabs :items="$natureItems" :active="$activeNature" />

<form method="GET" action="{{ route('catalog.index') }}" class="mt-4 flex flex-wrap gap-3">
    @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif

    <div class="min-w-[160px] flex-1 sm:max-w-[220px]">
        <x-ui.select
            name="category"
            :options="$categories->mapWithKeys(fn ($c) => [$c => $c])->all()"
            :selected="$category"
            placeholder="Toutes les catégories"
            onchange="this.form.submit()"
        />
    </div>

    <div class="min-w-[150px] sm:max-w-[190px]">
        <x-ui.select
            name="sort"
            :options="['recent' => 'Plus récents', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant']"
            :selected="$sort"
            onchange="this.form.submit()"
        />
    </div>

    <noscript><button class="h-11 rounded-md border border-line-strong px-4 text-sm font-semibold">Filtrer</button></noscript>
</form>
