@extends('layouts.app')

@section('title', $offer ? 'Modifier — '.$offer->title : 'Nouvelle offre')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    $isNew = $offer === null;
    $type = old('type', $presetType ?? $offer?->type ?? 'physical');
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'][$type] ?? $type;
    $typeHelp = [
        'physical' => 'Stock, variantes et livraison suivie.',
        'digital' => 'Livraison automatique par lien sécurisé après paiement.',
        'service' => 'Réservation d\'un créneau, en ligne ou sur place.',
        'training' => 'Parcours en autonomie, en cohorte ou en direct.',
    ][$type] ?? '';
    $d = fn ($k, $default = null) => old("detail.$k", data_get($offer?->detail(), $k, $default));
    $cf = fn ($k) => old("custom_fields.$k", data_get($offer?->custom_fields, $k));
    $live = $offer && $offer->first_published_at !== null;
    $currency = $offer?->currency ?? 'XOF';

    $existingVariants = ($offer?->relationLoaded('variants') ? $offer->variants : collect())
        ->map(fn ($v) => ['id' => $v->id, 'values' => $v->option_values ?: new stdClass, 'stock' => $v->stock,
            'price' => $v->price_override, 'sku' => $v->sku, 'active' => $v->is_active])->values();
    $existingAxes = collect($offer?->variants ?? [])->flatMap(fn ($v) => array_keys($v->option_values ?? []))->unique()->values();

    $categoryOptions = $categories
        ->filter(fn ($c) => $c->allowsType($type))
        ->mapWithKeys(fn ($c) => [$c->id => str_repeat('— ', $c->depth).$c->name])
        ->all();

    // Classes communes (identiques au composant x-ui.field).
    $input = 'min-h-11 w-full rounded-xl border bg-surface px-3.5 py-2 text-[15px] text-ink placeholder:text-ink-3 transition-colors focus:outline-none focus:ring-2 ';
    $ok = 'border-line-strong hover:border-ink-3 focus:border-accent focus:ring-accent/20';
    $bad = 'border-rust focus:border-rust focus:ring-rust/20';
    $cls = fn ($key) => $input.($errors->has($key) ? $bad : $ok);
    $labelCls = 'text-sm font-semibold text-ink';
    $helpCls = 'text-[13px] leading-snug text-ink-3';
    $errCls = 'text-[13px] font-medium text-rust';
    $small = 'min-h-10 rounded-lg border border-line-strong bg-surface px-2.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="mx-auto max-w-3xl">

    {{-- En-tête --}}
    <a href="{{ route('vendor.offers.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink-2 hover:text-ink">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Mes offres
    </a>
    <header class="mt-2 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">{{ $isNew ? 'Nouvelle offre' : $offer->title }}</h1>
            @unless ($isNew)
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <x-ui.status-pill :status="$offer->status" />
                    @if ($live)<span class="text-[13px] text-ink-2">Vos modifications remplaceront la version en ligne après validation.</span>@endif
                </div>
            @endunless
        </div>
        @if ($offer?->status === 'approved' && $offer->is_online)
            <x-ui.button variant="secondary" :href="route('marketplace.shop.offer', $offer->slug)">Voir en ligne</x-ui.button>
        @endif
    </header>

    @if ($offer?->review_note && in_array($offer->status, ['changes_requested', 'rejected'], true))
        <x-ui.alert tone="warning" class="mt-5">Retour de l'équipe : {{ $offer->review_note }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert tone="error" class="mt-5">Certains champs sont à corriger. Ils sont signalés en rouge ci-dessous.</x-ui.alert>
    @endif

    <form id="offer-form" method="POST" enctype="multipart/form-data"
          action="{{ $isNew ? route('vendor.offers.store') : route('vendor.offers.update', $offer) }}"
          class="mt-6 space-y-6">
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <input type="hidden" name="type" value="{{ $type }}">

        {{-- 1. Type --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="step-type">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-sm font-semibold text-space-vendor" aria-hidden="true">1</span>
                <h2 id="step-type" class="font-sans text-lg font-semibold text-ink">Type d'offre</h2>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-ground px-4 py-3">
                <div>
                    <p class="text-[15px] font-semibold text-ink">{{ $typeLabel }}</p>
                    <p class="{{ $helpCls }}">{{ $typeHelp }}</p>
                </div>
                @if ($isNew)
                    <a href="{{ route('vendor.offers.create') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">Changer de type</a>
                @endif
            </div>
            <p class="mt-2 {{ $helpCls }}">Le type détermine les informations demandées. Il ne peut plus être modifié une fois le brouillon créé.</p>
        </section>

        {{-- 2. Description et médias --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="step-desc">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-sm font-semibold text-space-vendor" aria-hidden="true">2</span>
                <h2 id="step-desc" class="font-sans text-lg font-semibold text-ink">Description et médias</h2>
            </div>

            <div class="mt-5 space-y-5">
                <x-ui.select name="category_id" label="Catégorie" :required="true" placeholder="Choisir une catégorie"
                    :options="$categoryOptions" :selected="$offer?->category_id"
                    hint="Les caractéristiques propres à la catégorie s'affichent après enregistrement." />

                <x-ui.field name="title" label="Titre" :required="true" :value="$offer?->title"
                    hint="Le nom affiché sur la fiche et dans les résultats." />

                <x-ui.field name="summary" label="Accroche" :value="$offer?->summary" maxlength="255"
                    hint="Une phrase courte qui résume l'offre (255 caractères maximum)." />

                <x-ui.textarea name="description" label="Description" :rows="8" :required="true"
                    hint="Ce que l'acheteur reçoit, pour qui, comment cela se déroule.">{{ $offer?->description }}</x-ui.textarea>
            </div>

            {{-- Photos --}}
            <div class="mt-8 border-t border-line pt-6"
                 x-data="{ main: null, gallery: [], pick(e, t) { t === 'main' ? this.main = e.target.files[0] ? URL.createObjectURL(e.target.files[0]) : null : this.gallery = [...e.target.files].map(f => URL.createObjectURL(f)); } }">
                <h3 class="text-base font-semibold text-ink">Photos</h3>
                <p class="mt-1 {{ $helpCls }}">Une image principale nette et jusqu'à 12 photos. JPG, PNG ou WebP, 5 Mo maximum chacune.</p>

                <div class="mt-4 grid gap-6 sm:grid-cols-[11rem_1fr]">
                    <div>
                        <label for="main_image" class="{{ $labelCls }}">Image principale</label>
                        <div class="mt-2 aspect-square overflow-hidden rounded-xl border border-line bg-ground-alt">
                            <template x-if="main"><img :src="main" alt="Aperçu de l'image principale" class="h-full w-full object-cover"></template>
                            <template x-if="!main">
                                @if ($offer?->main_image_path)
                                    <img src="{{ Storage::url($offer->main_image_path) }}" alt="Image principale actuelle" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-ink-3"><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 16l4-4 4 4 4-6 4 6M4 5h16v14H4z"/></svg></div>
                                @endif
                            </template>
                        </div>
                        <input id="main_image" type="file" name="main_image" accept="image/*" x-on:change="pick($event,'main')"
                               class="mt-3 block w-full text-sm text-ink-2 file:mr-3 file:min-h-10 file:rounded-full file:border file:border-line-strong file:bg-surface file:px-4 file:text-sm file:font-semibold file:text-ink">
                        @if ($offer?->main_image_path)
                            <label class="mt-2 flex min-h-11 items-center gap-2 text-sm text-ink-2"><input type="checkbox" name="remove_main_image" value="1" class="h-4 w-4 rounded border-line-strong text-accent">Retirer l'image actuelle</label>
                        @endif
                        @error('main_image')<p class="mt-1.5 {{ $errCls }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="gallery" class="{{ $labelCls }}">Galerie</label>
                        @if (! empty($offer?->gallery))
                            <p class="mt-1 {{ $helpCls }}">Cochez une photo pour la retirer.</p>
                            <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                                @foreach ($offer->gallery as $path)
                                    <label class="relative block cursor-pointer">
                                        <img src="{{ Storage::url($path) }}" alt="Photo {{ $loop->iteration }} de la galerie" class="aspect-square w-full rounded-lg border border-line object-cover">
                                        <span class="absolute right-1.5 top-1.5 flex items-center gap-1 rounded-full bg-surface px-2 py-1 text-xs font-semibold text-rust shadow-[0_1px_2px_rgba(28,25,23,0.08)]">
                                            <input type="checkbox" name="gallery_remove[]" value="{{ $path }}" class="h-3.5 w-3.5 rounded border-line-strong text-rust">Retirer
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <input id="gallery" type="file" name="gallery[]" accept="image/*" multiple x-on:change="pick($event,'gallery')"
                               class="mt-3 block w-full text-sm text-ink-2 file:mr-3 file:min-h-10 file:rounded-full file:border file:border-line-strong file:bg-surface file:px-4 file:text-sm file:font-semibold file:text-ink">
                        <div class="mt-2 flex flex-wrap gap-2"><template x-for="(g,i) in gallery" :key="i"><img :src="g" alt="" class="h-14 w-14 rounded-lg border border-line object-cover"></template></div>
                        @error('gallery.*')<p class="mt-1.5 {{ $errCls }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Caractéristiques de la catégorie --}}
            <div class="mt-8 border-t border-line pt-6">
                <h3 class="text-base font-semibold text-ink">Caractéristiques</h3>
                @if ($schema->isEmpty())
                    <p class="mt-1 {{ $helpCls }}">
                        {{ $isNew ? 'Choisissez une catégorie puis créez le brouillon : ses caractéristiques apparaîtront ici.' : 'Cette catégorie n\'a pas de caractéristique particulière.' }}
                    </p>
                @else
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        @foreach ($schema as $field)
                            @php
                                $fid = 'cf-'.$field->key;
                                $fkey = "custom_fields.{$field->key}";
                            @endphp
                            <div class="flex flex-col gap-1.5">
                                <label for="{{ $fid }}" class="{{ $labelCls }}">{{ $field->label }}@if ($field->is_required)<span class="ml-0.5 text-rust" aria-hidden="true">*</span>@endif</label>
                                @if (in_array($field->type, ['text', 'url', 'date'], true))
                                    <input id="{{ $fid }}" name="custom_fields[{{ $field->key }}]" value="{{ $cf($field->key) }}" class="{{ $cls($fkey) }}">
                                @elseif ($field->type === 'number')
                                    <input id="{{ $fid }}" type="number" step="any" name="custom_fields[{{ $field->key }}]" value="{{ $cf($field->key) }}" class="{{ $cls($fkey) }}">
                                @elseif ($field->type === 'textarea')
                                    <textarea id="{{ $fid }}" name="custom_fields[{{ $field->key }}]" rows="3" class="{{ $cls($fkey) }} py-2.5">{{ $cf($field->key) }}</textarea>
                                @elseif ($field->type === 'boolean')
                                    <select id="{{ $fid }}" name="custom_fields[{{ $field->key }}]" class="{{ $cls($fkey) }}">
                                        <option value="">—</option><option value="1" @selected($cf($field->key) == '1')>Oui</option><option value="0" @selected($cf($field->key) === '0')>Non</option>
                                    </select>
                                @elseif ($field->type === 'select')
                                    <select id="{{ $fid }}" name="custom_fields[{{ $field->key }}]" class="{{ $cls($fkey) }}">
                                        <option value="">—</option>
                                        @foreach ($field->options ?? [] as $opt)<option value="{{ $opt['value'] }}" @selected($cf($field->key) === $opt['value'])>{{ $opt['label'] }}</option>@endforeach
                                    </select>
                                @elseif ($field->type === 'multiselect')
                                    <select id="{{ $fid }}" name="custom_fields[{{ $field->key }}][]" multiple size="3" class="{{ $cls($fkey) }}">
                                        @foreach ($field->options ?? [] as $opt)<option value="{{ $opt['value'] }}" @selected(in_array($opt['value'], (array) $cf($field->key), true))>{{ $opt['label'] }}</option>@endforeach
                                    </select>
                                @endif
                                @if ($field->help_text)<p class="{{ $helpCls }}">{{ $field->help_text }}</p>@endif
                                @error($fkey)<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- 3. Prix, stock et créneaux --}}
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="step-price">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-sm font-semibold text-space-vendor" aria-hidden="true">3</span>
                <h2 id="step-price" class="font-sans text-lg font-semibold text-ink">
                    {{ match ($type) { 'service' => 'Prix et créneaux', 'physical' => 'Prix et stock', default => 'Prix et accès' } }}
                </h2>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach (['price' => ['Prix', true, 'Prix de vente affiché.'], 'sale_price' => ['Prix promotionnel', false, 'Facultatif. Laissez vide s\'il n\'y a pas de promotion.']] as $pname => [$plabel, $preq, $phelp])
                    <div class="flex flex-col gap-1.5">
                        <label for="{{ $pname }}" class="{{ $labelCls }}">{{ $plabel }}@if ($preq)<span class="ml-0.5 text-rust" aria-hidden="true">*</span>@endif</label>
                        <div @class([
                            'flex min-h-11 items-stretch overflow-hidden rounded-xl border bg-surface focus-within:ring-2',
                            'border-rust focus-within:ring-rust/20' => $errors->has($pname),
                            'border-line-strong focus-within:border-accent focus-within:ring-accent/20' => ! $errors->has($pname),
                        ])>
                            <span class="flex items-center border-r border-line bg-ground-alt px-3 text-[13px] font-semibold text-ink-2">{{ $currency }}</span>
                            <input id="{{ $pname }}" name="{{ $pname }}" type="number" step="0.01" min="0" value="{{ old($pname, $offer?->{$pname}) }}"
                                   aria-describedby="{{ $pname }}-hint" class="min-w-0 flex-1 bg-transparent px-3.5 text-[15px] text-ink outline-none">
                        </div>
                        <p id="{{ $pname }}-hint" class="{{ $helpCls }}">{{ $phelp }}</p>
                        @error($pname)<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-line pt-6">
                @if ($type === 'physical')
                    <h3 class="text-base font-semibold text-ink">Stock et expédition</h3>
                    <div class="mt-4 grid gap-5 sm:grid-cols-3">
                        <x-ui.field name="detail[sku]" label="Référence (SKU)" :value="$d('sku')" />
                        <x-ui.field name="detail[stock]" label="Stock" type="number" min="0" :value="$d('stock', 0)" hint="Sans variante." />
                        <x-ui.field name="detail[weight_grams]" label="Poids (g)" type="number" min="0" :value="$d('weight_grams')" />
                        <x-ui.field name="detail[length_mm]" label="Longueur (mm)" type="number" min="0" :value="$d('length_mm')" />
                        <x-ui.field name="detail[width_mm]" label="Largeur (mm)" type="number" min="0" :value="$d('width_mm')" />
                        <x-ui.field name="detail[height_mm]" label="Hauteur (mm)" type="number" min="0" :value="$d('height_mm')" />
                        <div class="sm:col-span-2">
                            <x-ui.field name="detail[origin_region]" label="Provenance" :value="$d('origin_region')" />
                        </div>
                        <x-ui.field name="detail[handling_days]" label="Préparation (jours)" type="number" min="0" :value="$d('handling_days')" hint="Délai avant expédition." />
                    </div>
                @elseif ($type === 'digital')
                    <h3 class="text-base font-semibold text-ink">Livraison du fichier</h3>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <x-ui.select name="detail[delivery_type]" label="Mode de livraison" :selected="$d('delivery_type', 'file')"
                            :options="['file' => 'Fichier', 'external_link' => 'Lien externe', 'license_keys' => 'Clés de licence']" />
                        <x-ui.field name="detail[version_label]" label="Version" :value="$d('version_label')" hint="Exemple : 2e édition, v1.2." />
                        <x-ui.field name="detail[external_url]" label="URL externe" :value="$d('external_url')" hint="Pour une livraison par lien externe." />
                        <x-ui.field name="detail[file_path]" label="Chemin du fichier" :value="$d('file_path')" placeholder="uploads/…" hint="Pour une livraison par fichier." />
                    </div>
                @elseif ($type === 'service')
                    <h3 class="text-base font-semibold text-ink">Déroulement et créneaux</h3>
                    <div class="mt-4 grid gap-5 sm:grid-cols-3">
                        <x-ui.field name="detail[duration_minutes]" label="Durée (min)" type="number" min="5" :value="$d('duration_minutes', 60)" />
                        <x-ui.select name="detail[delivery_mode]" label="Mode" :selected="$d('delivery_mode', 'online')"
                            :options="['online' => 'En ligne', 'onsite' => 'Sur place', 'either' => 'Les deux']" />
                        <x-ui.field name="detail[capacity_per_slot]" label="Places par créneau" type="number" min="1" :value="$d('capacity_per_slot', 1)" />
                        <div class="sm:col-span-2">
                            <x-ui.field name="detail[location]" label="Lieu" :value="$d('location')" hint="Adresse pour les séances sur place." />
                        </div>
                        <x-ui.field name="detail[min_notice_hours]" label="Préavis (heures)" type="number" min="0" :value="$d('min_notice_hours', 24)" hint="Délai minimum avant un créneau." />
                    </div>
                @elseif ($type === 'training')
                    <h3 class="text-base font-semibold text-ink">Format et accès</h3>
                    <div class="mt-4 grid gap-5 sm:grid-cols-3">
                        <x-ui.select name="detail[format]" label="Format" :selected="$d('format', 'self_paced')"
                            :options="['self_paced' => 'En autonomie', 'cohort' => 'Cohorte', 'live' => 'En direct']" />
                        <x-ui.field name="detail[language]" label="Langue" :value="$d('language', 'fr')" hint="Code de langue, par exemple fr." />
                        <x-ui.select name="detail[level]" label="Niveau" placeholder="—" :selected="$d('level')"
                            :options="['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire', 'advanced' => 'Avancé']" />
                        <x-ui.field name="detail[seats]" label="Places" type="number" min="1" :value="$d('seats')" />
                        <x-ui.field name="detail[access_days]" label="Accès (jours)" type="number" min="1" :value="$d('access_days')" hint="Laissez vide pour un accès sans limite." />
                    </div>
                @endif
            </div>

            {{-- Variantes (produits physiques) --}}
            @if ($type === 'physical')
                <div class="mt-6 border-t border-line pt-6"
                     x-data="offerVariants({{ Illuminate\Support\Js::from($existingAxes) }}, {{ Illuminate\Support\Js::from($existingVariants) }})">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-ink">Variantes</h3>
                        <label class="flex min-h-11 items-center gap-2 text-sm font-medium text-ink">
                            <input type="checkbox" x-model="enabled" class="h-4 w-4 rounded border-line-strong text-accent">
                            Proposer des déclinaisons (couleur, taille…)
                        </label>
                    </div>
                    <template x-if="!enabled"><p class="mt-1 {{ $helpCls }}">Une seule version de ce produit : le stock est celui indiqué ci-dessus.</p></template>
                    <div x-show="enabled" x-cloak class="mt-4 space-y-4">
                        <div>
                            <p class="{{ $labelCls }}">Axes de déclinaison</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <template x-for="(axis,ai) in axes" :key="ai">
                                    <span class="inline-flex items-center gap-1 rounded-full border border-line-strong bg-surface py-1 pl-3 pr-1 text-sm">
                                        <input x-model="axes[ai]" class="w-24 bg-transparent capitalize outline-none" placeholder="Axe" aria-label="Nom de l'axe">
                                        <button type="button" x-on:click="axes.splice(ai,1)" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-ink-3 hover:bg-ground-alt hover:text-rust" aria-label="Retirer cet axe">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg>
                                        </button>
                                    </span>
                                </template>
                                <button type="button" x-on:click="axes.push('')" class="inline-flex min-h-10 items-center rounded-full border border-dashed border-line-strong px-3 text-sm font-semibold text-ink-2 hover:text-ink">Ajouter un axe</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[38rem] text-sm">
                                <thead class="text-left text-[13px] text-ink-3">
                                    <tr>
                                        <template x-for="axis in axes" :key="axis"><th scope="col" class="pb-2 pr-2 font-semibold capitalize" x-text="axis || 'Option'"></th></template>
                                        <th scope="col" class="pb-2 pr-2 font-semibold">Stock</th><th scope="col" class="pb-2 pr-2 font-semibold">Prix</th><th scope="col" class="pb-2 pr-2 font-semibold">SKU</th><th scope="col" class="pb-2 pr-2 font-semibold">Actif</th><th><span class="sr-only">Action</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row,ri) in rows" :key="ri">
                                        <tr class="border-t border-line">
                                            <template x-for="axis in axes" :key="axis">
                                                <td class="py-1.5 pr-2"><input x-model="row.values[axis]" :name="`variants[${ri}][option_values][${axis}]`" :aria-label="axis" class="{{ $small }} w-24"></td>
                                            </template>
                                            <td class="py-1.5 pr-2"><input type="number" min="0" x-model="row.stock" :name="`variants[${ri}][stock]`" aria-label="Stock" class="{{ $small }} w-20"></td>
                                            <td class="py-1.5 pr-2"><input type="number" min="0" step="0.01" x-model="row.price" :name="`variants[${ri}][price_override]`" aria-label="Prix" class="{{ $small }} w-24"></td>
                                            <td class="py-1.5 pr-2"><input x-model="row.sku" :name="`variants[${ri}][sku]`" aria-label="SKU" class="{{ $small }} w-28"></td>
                                            <td class="py-1.5 pr-2 text-center">
                                                <input type="checkbox" x-model="row.active" aria-label="Variante active" class="h-4 w-4 rounded border-line-strong text-accent">
                                                <input type="hidden" :name="`variants[${ri}][is_active]`" :value="row.active ? 1 : 0">
                                                <input type="hidden" :name="`variants[${ri}][id]`" :value="row.id || ''">
                                            </td>
                                            <td class="py-1.5"><button type="button" x-on:click="rows.splice(ri,1)" class="inline-flex min-h-10 items-center px-2 text-sm font-semibold text-ink-2 hover:text-rust">Retirer</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" x-on:click="rows.push({id:null,values:{},stock:0,price:'',sku:'',active:true})" class="inline-flex min-h-11 items-center rounded-full border border-dashed border-line-strong px-4 text-sm font-semibold text-ink-2 hover:text-ink">Ajouter une variante</button>
                        @error('variants')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif
        </section>
    </form>

    {{-- 4. Envoi --}}
    <section class="mt-6 rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="step-send">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-space-vendor-soft text-sm font-semibold text-space-vendor" aria-hidden="true">4</span>
            <h2 id="step-send" class="font-sans text-lg font-semibold text-ink">Enregistrement et envoi</h2>
        </div>
        <p class="mt-2 text-sm leading-relaxed text-ink-2">
            @if ($isNew)
                Créez d'abord le brouillon. Vous pourrez ensuite le compléter et le soumettre à validation ; l'équipe relit chaque offre avant sa mise en ligne.
            @else
                Enregistrez vos modifications, puis soumettez l'offre à validation. L'équipe relit chaque offre avant sa mise en ligne.
            @endif
        </p>

        <details class="group mt-5 rounded-xl border border-line" @if ($errors->has('seo_title') || $errors->has('seo_description')) open @endif>
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 text-sm font-semibold text-ink">
                Référencement (facultatif)
                <svg class="h-4 w-4 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <div class="space-y-5 border-t border-line p-4">
                <x-ui.field name="seo_title" label="Titre pour les moteurs de recherche" form="offer-form" maxlength="160" :value="$offer?->seo_title" hint="160 caractères maximum. Par défaut, le titre de l'offre est utilisé." />
                <x-ui.textarea name="seo_description" label="Description pour les moteurs de recherche" form="offer-form" :rows="3" maxlength="255" hint="255 caractères maximum.">{{ $offer?->seo_description }}</x-ui.textarea>
            </div>
        </details>

        <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-line pt-5">
            <x-ui.button form="offer-form" type="submit" variant="primary">{{ $isNew ? 'Créer le brouillon' : 'Enregistrer' }}</x-ui.button>

            @unless ($isNew)
                @if ($offer->canBeSubmitted() || ($live && $offer->revisions()->where('state', 'pending')->exists()))
                    <form method="POST" action="{{ route('vendor.offers.submit', $offer) }}">@csrf
                        <button class="inline-flex min-h-11 items-center rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">Soumettre à validation</button>
                    </form>
                @endif
                @if ($offer->status === 'pending_review')
                    <form method="POST" action="{{ route('vendor.offers.withdraw', $offer) }}">@csrf
                        <button class="inline-flex min-h-11 items-center rounded-full border border-line-strong bg-surface px-5 text-sm font-semibold text-ink hover:border-ink">Retirer la soumission</button>
                    </form>
                @endif
                @if ($offer->status === 'approved')
                    <form method="POST" action="{{ route($offer->is_online ? 'vendor.offers.unpublish' : 'vendor.offers.publish', $offer) }}">@csrf
                        <button class="inline-flex min-h-11 items-center rounded-full border border-line-strong bg-surface px-5 text-sm font-semibold text-ink hover:border-ink">{{ $offer->is_online ? 'Retirer de la vente' : 'Remettre en vente' }}</button>
                    </form>
                @endif
                @unless ($offer->status === 'pending_review')
                    <form method="POST" action="{{ route('vendor.offers.destroy', $offer) }}" onsubmit="return confirm('Supprimer cette offre ?')" class="sm:ml-auto">@csrf @method('DELETE')
                        <button class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-semibold text-rust underline decoration-rust/40 underline-offset-4 hover:decoration-rust">Supprimer l'offre</button>
                    </form>
                @endunless
            @endunless
        </div>
    </section>
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
