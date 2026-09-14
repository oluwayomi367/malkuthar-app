@extends('layouts.storefront')

@section('title', 'Place de marché' . ($category ? ' — ' . $category->name : ''))
@section('seo_description', "Les offres des praticiens, créateurs et artisans de la région sur MALKUTHAR : produits, services et formations validés par notre équipe.")

@section('content')
@php $q = request()->query(); @endphp

<section class="border-b border-line bg-ground-alt/50">
    <div class="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-14">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
        <h1 class="mt-2 font-display text-2xl font-bold tracking-[-0.012em] text-ink sm:text-4xl">
            {{ $category->name ?? 'Les offres de nos vendeurs' }}
        </h1>
        <p class="mt-2 max-w-xl text-ink-2">
            {{ $category?->description ?? "Praticiens, créateurs, formateurs et artisans de la région. Chaque offre est validée par notre équipe avant sa mise en ligne." }}
        </p>

        @unless ($category)
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($rootCategories as $c)
                    <a href="{{ route('marketplace.shop', ['categorie' => $c->slug]) }}"
                       class="rounded-full border border-line bg-surface px-3 py-1.5 text-sm text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        @endunless
    </div>
</section>

<div class="mx-auto grid max-w-[1180px] gap-8 px-4 pb-20 pt-8 sm:px-6 lg:grid-cols-[220px_1fr]">

    {{-- ===== Filtres ===== --}}
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <form method="GET" class="space-y-5 text-sm">
            <div>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Recherche</p>
                <input name="q" value="{{ $q['q'] ?? '' }}" placeholder="Mot-clé"
                       class="h-9 w-full rounded-lg border border-line bg-surface px-3 text-sm">
            </div>

            <div>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Catégories</p>
                <ul class="space-y-1">
                    <li><a href="{{ route('marketplace.shop') }}" @class(['hover:text-ink', 'font-semibold text-accent' => ! $category, 'text-ink-2' => $category])>Toutes</a></li>
                    @foreach ($rootCategories as $c)
                        <li><a href="{{ route('marketplace.shop', ['categorie' => $c->slug]) }}"
                               @class(['hover:text-ink', 'font-semibold text-accent' => $category?->id === $c->id, 'text-ink-2' => $category?->id !== $c->id])>{{ $c->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Type</p>
                <select name="type" class="h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                    <option value="">Tous</option>
                    @foreach (['physical' => 'Produits physiques', 'digital' => 'Produits digitaux', 'service' => 'Services', 'training' => 'Formations'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($activeType === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Prix</p>
                <div class="flex gap-2">
                    <input name="prix_min" type="number" min="0" value="{{ $q['prix_min'] ?? '' }}" placeholder="min" class="h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                    <input name="prix_max" type="number" min="0" value="{{ $q['prix_max'] ?? '' }}" placeholder="max" class="h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                </div>
            </div>

            <div>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">Région</p>
                <input name="region" value="{{ $q['region'] ?? '' }}" placeholder="Région du vendeur"
                       class="h-9 w-full rounded-lg border border-line bg-surface px-3 text-sm">
            </div>

            @if ($category)
                <input type="hidden" name="categorie" value="{{ $category->slug }}">
            @endif

            @foreach ($facetFields as $field)
                <div>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-3">{{ $field->label }}</p>
                    <select name="f[{{ $field->key }}]" class="h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                        <option value="">Toutes</option>
                        @foreach ($field->options ?? [] as $opt)
                            <option value="{{ $opt['value'] }}" @selected(($activeFacets[$field->key] ?? null) === $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div class="flex gap-2 pt-1">
                <button class="h-9 flex-1 rounded-lg bg-accent text-sm font-semibold text-white">Filtrer</button>
                <a href="{{ route('marketplace.shop') }}" class="flex h-9 items-center rounded-lg border border-line px-3 text-sm text-ink-2">Réinitialiser</a>
            </div>
        </form>
    </aside>

    {{-- ===== Résultats ===== --}}
    <div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-2">
            <span>{{ $offers->total() }} offre{{ $offers->total() > 1 ? 's' : '' }}</span>
            <form method="GET">
                @foreach ($q as $k => $v)
                    @continue($k === 'tri' || is_array($v))
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                @foreach ((array) ($q['f'] ?? []) as $fk => $fv)
                    <input type="hidden" name="f[{{ $fk }}]" value="{{ $fv }}">
                @endforeach
                <select name="tri" onchange="this.form.submit()" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
                    <option value="">Plus récentes</option>
                    <option value="prix_asc" @selected(($q['tri'] ?? '') === 'prix_asc')>Prix croissant</option>
                    <option value="prix_desc" @selected(($q['tri'] ?? '') === 'prix_desc')>Prix décroissant</option>
                    <option value="populaire" @selected(($q['tri'] ?? '') === 'populaire')>Les plus vendues</option>
                </select>
            </form>
        </div>

        @if ($offers->isEmpty())
            <div class="rounded-2xl border border-dashed border-line-strong bg-surface p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-line-strong" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <p class="mt-3 font-display text-base font-semibold text-ink">Aucune offre trouvée</p>
                <p class="mt-1 text-sm text-ink-2">Essayez d'élargir votre recherche ou de changer de catégorie.</p>
                <div class="mt-4"><x-ui.button variant="secondary" :href="route('marketplace.shop')">Voir toutes les offres</x-ui.button></div>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($offers as $offer)
                    <x-marketplace.offer-card :offer="$offer" />
                @endforeach
            </div>
            <x-ui.pagination :paginator="$offers" class="mt-8" />
        @endif
    </div>
</div>
@endsection
