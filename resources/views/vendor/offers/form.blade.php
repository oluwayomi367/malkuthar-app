@extends('layouts.app')

@section('title', $offer ? 'Modifier — '.$offer->title : 'Nouvelle offre')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    $isNew = $offer === null;
    $type = old('type', $presetType ?? $offer?->type ?? 'physical');
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'][$type] ?? $type;
    $d = fn ($k, $default = null) => old("detail.$k", data_get($offer?->detail(), $k, $default));
    $cf = fn ($k) => old("custom_fields.$k", data_get($offer?->custom_fields, $k));
    $live = $offer && $offer->first_published_at !== null;

    $existingVariants = ($offer?->relationLoaded('variants') ? $offer->variants : collect())
        ->map(fn ($v) => ['id' => $v->id, 'values' => $v->option_values ?: new stdClass, 'stock' => $v->stock,
            'price' => $v->price_override, 'sku' => $v->sku, 'active' => $v->is_active])->values();
    $existingAxes = collect($offer?->variants ?? [])->flatMap(fn ($v) => array_keys($v->option_values ?? []))->unique()->values();

    // Onglet à ouvrir si des erreurs de validation existent.
    $tabOf = [
        'infos' => ['category_id', 'title', 'summary', 'description'],
        'price' => ['price', 'sale_price', 'detail'],
        'photos' => ['main_image', 'gallery'],
        'variants' => ['variants'],
        'specs' => ['custom_fields'],
        'seo' => ['seo_title', 'seo_description'],
    ];
    $openTab = 'infos';
    foreach ($tabOf as $tab => $keys) {
        foreach ($keys as $k) {
            if ($errors->has($k) || $errors->has($k.'.*') || collect($errors->keys())->contains(fn ($ek) => str_starts_with($ek, $k.'.'))) {
                $openTab = $tab;
                break 2;
            }
        }
    }

    $tabs = [
        'infos' => 'Informations',
        'price' => 'Prix & stock',
        'photos' => 'Photos',
        'variants' => 'Variantes',
        'specs' => 'Caractéristiques',
        'seo' => 'Référencement',
    ];
    if ($type !== 'physical') unset($tabs['variants']);
    if ($schema->isEmpty() && $isNew) { /* garde l'onglet, il se remplit après choix de catégorie */ }
@endphp

<div class="mx-auto max-w-4xl" x-data="{ tab: '{{ $openTab }}' }">

    {{-- ===== Barre d'action ===== --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">{{ $typeLabel }}</p>
            <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{{ $isNew ? 'Nouvelle offre' : $offer->title }}</h1>
            @unless ($isNew)
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <x-ui.status-pill :status="$offer->status" />
                    @if ($live)<span class="text-xs text-ink-3">Vos modifications remplaceront la version en ligne après validation.</span>@endif
                </div>
            @endunless
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($offer?->status === 'approved' && $offer->is_online)
                <x-ui.button variant="secondary" :href="route('marketplace.shop.offer', $offer->slug)">Voir en ligne</x-ui.button>
            @endif
            <x-ui.button form="offer-form" type="submit" variant="primary">{{ $isNew ? 'Créer le brouillon' : 'Enregistrer' }}</x-ui.button>
        </div>
    </div>

    @if ($offer?->review_note && in_array($offer->status, ['changes_requested', 'rejected'], true))
        <x-ui.alert tone="warning" class="mt-4">Retour de l'équipe : {{ $offer->review_note }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert tone="error" class="mt-4">Certains champs sont à corriger — voir les onglets signalés.</x-ui.alert>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[190px_1fr]">

        {{-- ===== Onglets latéraux ===== --}}
        <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-line bg-surface p-1.5 lg:sticky lg:top-24 lg:h-max lg:flex-col lg:self-start">
            @foreach ($tabs as $key => $label)
                @php $tabHasError = collect($tabOf[$key])->contains(fn ($k) => $errors->has($k) || collect($errors->keys())->contains(fn ($ek) => str_starts_with($ek, $k.'.'))); @endphp
                <button type="button" x-on:click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-accent-soft font-semibold text-accent' : 'text-ink-2 hover:bg-ground-alt hover:text-ink'"
                        class="flex shrink-0 items-center justify-between gap-2 rounded-xl px-3 py-2 text-left text-sm">
                    {{ $label }}
                    @if ($tabHasError)<span class="h-1.5 w-1.5 rounded-full bg-rust"></span>@endif
                </button>
            @endforeach
        </nav>

        {{-- ===== Panneaux ===== --}}
        <form id="offer-form" method="POST" enctype="multipart/form-data"
              action="{{ $isNew ? route('vendor.offers.store') : route('vendor.offers.update', $offer) }}"
              class="min-w-0">
            @csrf
            @unless ($isNew) @method('PUT') @endunless
            <input type="hidden" name="type" value="{{ $type }}">

            {{-- ---- Informations ---- --}}
            <section x-show="tab === 'infos'" class="space-y-4 rounded-2xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Informations</h2>
                <label class="block text-sm">
                    <span class="text-ink-2">Catégorie *</span>
                    <select name="category_id" class="mt-1 h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                        <option value="">Choisir…</option>
                        @foreach ($categories as $c)
                            @continue(! $c->allowsType($type))
                            <option value="{{ $c->id }}" @selected(old('category_id', $offer?->category_id) === $c->id)>{{ str_repeat('— ', $c->depth) }}{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">
                    <span class="text-ink-2">Titre *</span>
                    <input name="title" value="{{ old('title', $offer?->title) }}" class="mt-1 h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                    @error('title')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">
                    <span class="text-ink-2">Accroche</span>
                    <input name="summary" value="{{ old('summary', $offer?->summary) }}" maxlength="255" class="mt-1 h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                </label>
                <label class="block text-sm">
                    <span class="text-ink-2">Description *</span>
                    <textarea name="description" rows="8" class="mt-1 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm">{{ old('description', $offer?->description) }}</textarea>
                    @error('description')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                </label>
            </section>

            {{-- ---- Prix & stock ---- --}}
            <section x-show="tab === 'price'" x-cloak class="space-y-4 rounded-2xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Prix & stock</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="text-ink-2">Prix *</span>
                        <span class="mt-1 flex h-11 items-center overflow-hidden rounded-xl border border-line bg-surface">
                            <span class="border-r border-line bg-ground-alt px-3 text-xs font-semibold text-ink-3">{{ $offer?->currency ?? 'XOF' }}</span>
                            <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $offer?->price) }}" class="h-full min-w-0 flex-1 bg-transparent px-3 text-sm outline-none">
                        </span>
                        @error('price')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-ink-2">Prix promotionnel</span>
                        <span class="mt-1 flex h-11 items-center overflow-hidden rounded-xl border border-line bg-surface">
                            <span class="border-r border-line bg-ground-alt px-3 text-xs font-semibold text-ink-3">{{ $offer?->currency ?? 'XOF' }}</span>
                            <input name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $offer?->sale_price) }}" class="h-full min-w-0 flex-1 bg-transparent px-3 text-sm outline-none">
                        </span>
                        @error('sale_price')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                    </label>
                </div>

                @if ($type === 'physical')
                    <div class="grid gap-3 rounded-xl border border-line bg-ground-alt/40 p-3 sm:grid-cols-3">
                        <label class="text-sm">SKU<input name="detail[sku]" value="{{ $d('sku') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Stock (sans variante)<input name="detail[stock]" type="number" min="0" value="{{ $d('stock', 0) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Poids (g)<input name="detail[weight_grams]" type="number" min="0" value="{{ $d('weight_grams') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Longueur (mm)<input name="detail[length_mm]" type="number" min="0" value="{{ $d('length_mm') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Largeur (mm)<input name="detail[width_mm]" type="number" min="0" value="{{ $d('width_mm') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Hauteur (mm)<input name="detail[height_mm]" type="number" min="0" value="{{ $d('height_mm') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm sm:col-span-2">Provenance<input name="detail[origin_region]" value="{{ $d('origin_region') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Préparation (j)<input name="detail[handling_days]" type="number" min="0" value="{{ $d('handling_days') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                    </div>
                @elseif ($type === 'digital')
                    <div class="grid gap-3 rounded-xl border border-line bg-ground-alt/40 p-3 sm:grid-cols-2">
                        <label class="text-sm">Livraison
                            <select name="detail[delivery_type]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2">
                                @foreach (['file' => 'Fichier', 'external_link' => 'Lien externe', 'license_keys' => 'Clés de licence'] as $k => $l)
                                    <option value="{{ $k }}" @selected($d('delivery_type', 'file') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm">URL externe<input name="detail[external_url]" value="{{ $d('external_url') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Chemin fichier<input name="detail[file_path]" value="{{ $d('file_path') }}" placeholder="uploads/…" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Version<input name="detail[version_label]" value="{{ $d('version_label') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                    </div>
                @elseif ($type === 'service')
                    <div class="grid gap-3 rounded-xl border border-line bg-ground-alt/40 p-3 sm:grid-cols-3">
                        <label class="text-sm">Durée (min)<input name="detail[duration_minutes]" type="number" min="5" value="{{ $d('duration_minutes', 60) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Mode
                            <select name="detail[delivery_mode]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2">
                                @foreach (['online' => 'En ligne', 'onsite' => 'Sur place', 'either' => 'Les deux'] as $k => $l)
                                    <option value="{{ $k }}" @selected($d('delivery_mode', 'online') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm">Places / créneau<input name="detail[capacity_per_slot]" type="number" min="1" value="{{ $d('capacity_per_slot', 1) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm sm:col-span-2">Lieu<input name="detail[location]" value="{{ $d('location') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Préavis (h)<input name="detail[min_notice_hours]" type="number" min="0" value="{{ $d('min_notice_hours', 24) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                    </div>
                @elseif ($type === 'training')
                    <div class="grid gap-3 rounded-xl border border-line bg-ground-alt/40 p-3 sm:grid-cols-3">
                        <label class="text-sm">Format
                            <select name="detail[format]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2">
                                @foreach (['self_paced' => 'En autonomie', 'cohort' => 'Cohorte', 'live' => 'Live'] as $k => $l)
                                    <option value="{{ $k }}" @selected($d('format', 'self_paced') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm">Langue<input name="detail[language]" value="{{ $d('language', 'fr') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Niveau
                            <select name="detail[level]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2">
                                <option value="">—</option>
                                @foreach (['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire', 'advanced' => 'Avancé'] as $k => $l)
                                    <option value="{{ $k }}" @selected($d('level') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm">Places<input name="detail[seats]" type="number" min="1" value="{{ $d('seats') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                        <label class="text-sm">Accès (jours)<input name="detail[access_days]" type="number" min="1" value="{{ $d('access_days') }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2"></label>
                    </div>
                @endif
            </section>

            {{-- ---- Photos ---- --}}
            <section x-show="tab === 'photos'" x-cloak class="rounded-2xl border border-line bg-surface p-5"
                     x-data="{ main: null, gallery: [], pick(e, t) { t === 'main' ? this.main = e.target.files[0] ? URL.createObjectURL(e.target.files[0]) : null : this.gallery = [...e.target.files].map(f => URL.createObjectURL(f)); } }">
                <h2 class="font-display text-base font-semibold text-ink">Photos</h2>
                <p class="mt-1 text-xs text-ink-3">Image principale nette + jusqu'à 12 photos. JPG, PNG ou WebP, 5 Mo max.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-[10rem_1fr]">
                    <div>
                        <div class="aspect-square overflow-hidden rounded-xl border border-line bg-ground-alt">
                            <template x-if="main"><img :src="main" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="!main">
                                @if ($offer?->main_image_path)
                                    <img src="{{ Storage::url($offer->main_image_path) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-line-strong"><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 16l4-4 4 4 4-6 4 6M4 5h16v14H4z"/></svg></div>
                                @endif
                            </template>
                        </div>
                        <input type="file" name="main_image" accept="image/*" x-on:change="pick($event,'main')" class="mt-2 block w-full text-xs text-ink-2 file:mr-2 file:rounded-lg file:border-0 file:bg-ground-alt file:px-2 file:py-1.5">
                        @if ($offer?->main_image_path)
                            <label class="mt-1 flex items-center gap-1.5 text-xs text-ink-3"><input type="checkbox" name="remove_main_image" value="1" class="h-3.5 w-3.5 rounded border-line-strong text-accent">Retirer</label>
                        @endif
                        @error('main_image')<p class="text-xs text-rust">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        @if (! empty($offer?->gallery))
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ($offer->gallery as $path)
                                    <label class="relative block">
                                        <img src="{{ Storage::url($path) }}" alt="" class="aspect-square w-full rounded-lg border border-line object-cover">
                                        <span class="absolute right-1 top-1 flex items-center gap-1 rounded bg-ground/90 px-1 text-[10px] text-rust"><input type="checkbox" name="gallery_remove[]" value="{{ $path }}" class="h-3 w-3 rounded border-line-strong text-rust">×</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" name="gallery[]" accept="image/*" multiple x-on:change="pick($event,'gallery')" class="mt-2 block w-full text-xs text-ink-2 file:mr-2 file:rounded-lg file:border-0 file:bg-ground-alt file:px-2 file:py-1.5">
                        <div class="mt-2 flex flex-wrap gap-2"><template x-for="(g,i) in gallery" :key="i"><img :src="g" alt="" class="h-14 w-14 rounded-lg border border-line object-cover"></template></div>
                        @error('gallery.*')<p class="text-xs text-rust">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- ---- Variantes ---- --}}
            @if ($type === 'physical')
                <section x-show="tab === 'variants'" x-cloak class="rounded-2xl border border-line bg-surface p-5"
                         x-data="offerVariants({{ Illuminate\Support\Js::from($existingAxes) }}, {{ Illuminate\Support\Js::from($existingVariants) }})">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-base font-semibold text-ink">Variantes</h2>
                        <label class="flex items-center gap-2 text-sm text-ink-2">
                            <input type="checkbox" x-model="enabled" class="h-4 w-4 rounded border-line-strong text-accent">
                            Déclinaisons (couleur, taille…)
                        </label>
                    </div>
                    <template x-if="!enabled"><p class="mt-3 text-sm text-ink-3">Une seule version de ce produit — le stock est celui de l'onglet « Prix & stock ».</p></template>
                    <div x-show="enabled" x-cloak class="mt-4 space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-for="(axis,ai) in axes" :key="ai">
                                <span class="inline-flex items-center gap-1 rounded-lg border border-line bg-ground-alt px-2 py-1 text-xs">
                                    <input x-model="axes[ai]" class="w-20 bg-transparent capitalize outline-none" placeholder="Axe">
                                    <button type="button" x-on:click="axes.splice(ai,1)" class="text-ink-3 hover:text-rust">×</button>
                                </span>
                            </template>
                            <button type="button" x-on:click="axes.push('')" class="rounded-lg border border-dashed border-line-strong px-2 py-1 text-xs text-ink-2">+ axe</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[38rem] text-sm">
                                <thead class="text-left text-[11px] uppercase text-ink-3">
                                    <tr>
                                        <template x-for="axis in axes" :key="axis"><th class="pb-1 pr-2 capitalize" x-text="axis || 'Option'"></th></template>
                                        <th class="pb-1 pr-2">Stock</th><th class="pb-1 pr-2">Prix</th><th class="pb-1 pr-2">SKU</th><th class="pb-1 pr-2">Actif</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row,ri) in rows" :key="ri">
                                        <tr class="border-t border-line">
                                            <template x-for="axis in axes" :key="axis">
                                                <td class="py-1 pr-2"><input x-model="row.values[axis]" :name="`variants[${ri}][option_values][${axis}]`" class="h-8 w-24 rounded border border-line bg-surface px-1.5"></td>
                                            </template>
                                            <td class="py-1 pr-2"><input type="number" min="0" x-model="row.stock" :name="`variants[${ri}][stock]`" class="h-8 w-16 rounded border border-line bg-surface px-1.5"></td>
                                            <td class="py-1 pr-2"><input type="number" min="0" step="0.01" x-model="row.price" :name="`variants[${ri}][price_override]`" class="h-8 w-20 rounded border border-line bg-surface px-1.5"></td>
                                            <td class="py-1 pr-2"><input x-model="row.sku" :name="`variants[${ri}][sku]`" class="h-8 w-24 rounded border border-line bg-surface px-1.5"></td>
                                            <td class="py-1 pr-2 text-center">
                                                <input type="checkbox" x-model="row.active" class="h-4 w-4 rounded border-line-strong text-accent">
                                                <input type="hidden" :name="`variants[${ri}][is_active]`" :value="row.active ? 1 : 0">
                                                <input type="hidden" :name="`variants[${ri}][id]`" :value="row.id || ''">
                                            </td>
                                            <td class="py-1"><button type="button" x-on:click="rows.splice(ri,1)" class="text-ink-3 hover:text-rust">Retirer</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" x-on:click="rows.push({id:null,values:{},stock:0,price:'',sku:'',active:true})" class="rounded-lg border border-dashed border-line-strong px-3 py-1.5 text-xs font-semibold text-ink-2">+ Ajouter une variante</button>
                        @error('variants')<p class="text-xs text-rust">{{ $message }}</p>@enderror
                    </div>
                </section>
            @endif

            {{-- ---- Caractéristiques ---- --}}
            <section x-show="tab === 'specs'" x-cloak class="rounded-2xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Caractéristiques</h2>
                @if ($schema->isEmpty())
                    <p class="mt-2 text-sm text-ink-3">Cette catégorie n'a pas de caractéristique particulière.</p>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($schema as $field)
                            <label class="block text-sm">
                                <span class="text-ink-2">{{ $field->label }}@if ($field->is_required) *@endif</span>
                                @if (in_array($field->type, ['text', 'url', 'date'], true))
                                    <input name="custom_fields[{{ $field->key }}]" value="{{ $cf($field->key) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                                @elseif ($field->type === 'number')
                                    <input type="number" step="any" name="custom_fields[{{ $field->key }}]" value="{{ $cf($field->key) }}" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                                @elseif ($field->type === 'textarea')
                                    <textarea name="custom_fields[{{ $field->key }}]" rows="2" class="mt-1 w-full rounded-lg border border-line bg-surface px-2 py-1.5 text-sm">{{ $cf($field->key) }}</textarea>
                                @elseif ($field->type === 'boolean')
                                    <select name="custom_fields[{{ $field->key }}]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                                        <option value="">—</option><option value="1" @selected($cf($field->key) == '1')>Oui</option><option value="0" @selected($cf($field->key) === '0')>Non</option>
                                    </select>
                                @elseif ($field->type === 'select')
                                    <select name="custom_fields[{{ $field->key }}]" class="mt-1 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                                        <option value="">—</option>
                                        @foreach ($field->options ?? [] as $opt)<option value="{{ $opt['value'] }}" @selected($cf($field->key) === $opt['value'])>{{ $opt['label'] }}</option>@endforeach
                                    </select>
                                @elseif ($field->type === 'multiselect')
                                    <select name="custom_fields[{{ $field->key }}][]" multiple size="3" class="mt-1 w-full rounded-lg border border-line bg-surface px-2 py-1 text-sm">
                                        @foreach ($field->options ?? [] as $opt)<option value="{{ $opt['value'] }}" @selected(in_array($opt['value'], (array) $cf($field->key), true))>{{ $opt['label'] }}</option>@endforeach
                                    </select>
                                @endif
                                @if ($field->help_text)<span class="text-[11px] text-ink-3">{{ $field->help_text }}</span>@endif
                                @error("custom_fields.{$field->key}")<span class="text-xs text-rust">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ---- Référencement ---- --}}
            <section x-show="tab === 'seo'" x-cloak class="space-y-4 rounded-2xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold text-ink">Référencement</h2>
                <label class="block text-sm"><span class="text-ink-2">Titre SEO</span>
                    <input name="seo_title" value="{{ old('seo_title', $offer?->seo_title) }}" maxlength="160" class="mt-1 h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm"></label>
                <label class="block text-sm"><span class="text-ink-2">Description SEO</span>
                    <textarea name="seo_description" rows="3" maxlength="255" class="mt-1 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm">{{ old('seo_description', $offer?->seo_description) }}</textarea></label>
            </section>

            <div class="mt-4">
                <x-ui.button type="submit" variant="primary">{{ $isNew ? 'Créer le brouillon' : 'Enregistrer' }}</x-ui.button>
            </div>
        </form>
    </div>

    {{-- ===== Cycle de vie ===== --}}
    @unless ($isNew)
        <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-4">
            @if ($offer->canBeSubmitted() || ($live && $offer->revisions()->where('state', 'pending')->exists()))
                <form method="POST" action="{{ route('vendor.offers.submit', $offer) }}">@csrf
                    <button class="h-10 rounded-xl border border-accent bg-accent/10 px-4 text-sm font-semibold text-accent">Soumettre à validation</button>
                </form>
            @endif
            @if ($offer->status === 'pending_review')
                <form method="POST" action="{{ route('vendor.offers.withdraw', $offer) }}">@csrf
                    <button class="h-10 rounded-xl border border-line px-4 text-sm font-semibold text-ink-2">Retirer la soumission</button>
                </form>
            @endif
            @if ($offer->status === 'approved')
                <form method="POST" action="{{ route($offer->is_online ? 'vendor.offers.unpublish' : 'vendor.offers.publish', $offer) }}">@csrf
                    <button class="h-10 rounded-xl border border-line px-4 text-sm font-semibold text-ink-2">{{ $offer->is_online ? 'Retirer de la vente' : 'Remettre en vente' }}</button>
                </form>
            @endif
            @unless ($offer->status === 'pending_review')
                <form method="POST" action="{{ route('vendor.offers.destroy', $offer) }}" onsubmit="return confirm('Supprimer cette offre ?')">@csrf @method('DELETE')
                    <button class="h-10 rounded-xl border border-rust/40 px-4 text-sm font-semibold text-rust">Supprimer</button>
                </form>
            @endunless
        </div>
    @endunless
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('offerVariants', (axes, rows) => ({
            enabled: rows.length > 0,
            axes: axes.length ? axes : ['Couleur', 'Taille'],
            rows: rows.map(r => ({ id: r.id, values: r.values || {}, stock: r.stock ?? 0, price: r.price ?? '', sku: r.sku ?? '', active: r.active ?? true })),
        }));
    });
</script>
@endpush
@endsection
