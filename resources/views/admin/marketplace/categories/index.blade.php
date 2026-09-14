@extends('layouts.admin')

@section('title', 'Administration — Catégories Marketplace')
@section('eyebrow', 'Marketplace')
@section('heading', 'Catalogue — catégories')

@section('content')
@php
    $types = ['physical' => 'Physique', 'digital' => 'Digital', 'service' => 'Service', 'training' => 'Formation'];
    $fieldTypes = [
        'text' => 'Texte', 'textarea' => 'Texte long', 'number' => 'Nombre', 'boolean' => 'Oui/Non',
        'select' => 'Liste (choix unique)', 'multiselect' => 'Liste (choix multiples)',
        'date' => 'Date', 'url' => 'Lien', 'file' => 'Fichier',
    ];
    $e = $editing;
    $ruleset = $e?->ruleset ?? ['require_approval' => true];
    $pct = fn ($r) => $r === null ? '' : rtrim(rtrim(number_format($r * 100, 2, '.', ''), '0'), '.');
@endphp

<div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">

    {{-- ============ ARBRE ============ --}}
    <div>
        <div class="rounded-2xl border border-black/10 bg-surface p-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">Arbre ({{ $categories->count() }})</h2>
                <a href="{{ route('admin.marketplace.categories.index') }}" class="text-xs font-semibold text-accent hover:underline">+ Nouvelle racine</a>
            </div>

            @if ($tree === [])
                <p class="mt-3 text-sm text-ink-3">Aucune catégorie. Créez-en une avec le formulaire.</p>
            @else
                <ul class="mt-3 space-y-0.5 text-sm">
                    @foreach ($tree as $row)
                        @php $cat = $row['category']; @endphp
                        <li>
                            <a href="{{ route('admin.marketplace.categories.index', ['edit' => $cat->id]) }}"
                               @class([
                                   'flex items-center justify-between rounded-lg px-2 py-1.5',
                                   'bg-accent/10 font-semibold text-accent' => $e && $e->id === $cat->id,
                                   'text-ink-2 hover:bg-black/[0.04] hover:text-ink' => ! ($e && $e->id === $cat->id),
                               ])
                               style="padding-left: {{ 0.5 + $row['depth'] * 1.1 }}rem">
                                <span>
                                    {{ $cat->name }}
                                    @unless ($cat->is_active)<span class="ml-1 text-[10px] uppercase text-rust">masquée</span>@endunless
                                </span>
                                <span class="text-[11px] text-ink-3">{{ $cat->fields_count }} ch.</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ============ FORMULAIRE ============ --}}
    <div class="space-y-6">
        <form method="POST"
              action="{{ $e ? route('admin.marketplace.categories.update', $e) : route('admin.marketplace.categories.store') }}"
              class="rounded-2xl border border-black/10 bg-surface p-5">
            @csrf
            @if ($e) @method('PUT') @endif

            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-semibold text-ink">{{ $e ? 'Modifier' : 'Nouvelle catégorie' }}</h2>
                @if ($e)
                    <button form="delete-cat-{{ $e->id }}" class="text-xs font-semibold text-rust hover:underline">Supprimer</button>
                @endif
            </div>

            <div class="mt-4 grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm">
                        <span class="text-ink-2">Nom *</span>
                        <input name="name" required value="{{ old('name', $e?->name) }}"
                               class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    </label>
                    <label class="text-sm">
                        <span class="text-ink-2">Parent</span>
                        <select name="parent_id" class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                            <option value="">— racine —</option>
                            @foreach ($categories as $c)
                                @continue($e && $c->id === $e->id)
                                <option value="{{ $c->id }}" @selected(old('parent_id', $e?->parent_id) === $c->id)>
                                    {{ str_repeat('— ', $c->depth) }}{{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="text-sm">
                    <span class="text-ink-2">Description</span>
                    <textarea name="description" rows="2"
                              class="mt-1 w-full rounded-xl border border-black/10 bg-surface px-3 py-2 text-sm">{{ old('description', $e?->description) }}</textarea>
                </label>

                <fieldset>
                    <legend class="text-sm text-ink-2">Types d'offres admis *</legend>
                    <div class="mt-1 flex flex-wrap gap-3">
                        @foreach ($types as $key => $lbl)
                            <label class="inline-flex items-center gap-1.5 text-sm">
                                <input type="checkbox" name="applicable_offer_types[]" value="{{ $key }}"
                                       @checked(in_array($key, old('applicable_offer_types', $e?->applicable_offer_types ?? ['physical','digital','service','training']), true))
                                       class="h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                                {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                    @error('applicable_offer_types')<p class="mt-1 text-xs text-rust">{{ $message }}</p>@enderror
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="text-sm">
                        <span class="text-ink-2">Commission (%)</span>
                        <input name="commission_rate_percent" type="number" step="0.01" min="0" max="90"
                               value="{{ old('commission_rate_percent', $pct($e?->commission_rate)) }}"
                               placeholder="défaut"
                               class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    </label>
                    <label class="text-sm">
                        <span class="text-ink-2">Position</span>
                        <input name="position" type="number" min="0" value="{{ old('position', $e?->position ?? 0) }}"
                               class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                    </label>
                    <label class="mt-6 inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $e?->is_active ?? true))
                               class="h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                        Active
                    </label>
                </div>

                <fieldset class="rounded-xl border border-black/[0.06] bg-ground-alt/50 p-3">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-ink-3">Règles</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="ruleset[require_approval]" value="1"
                                   @checked(old('ruleset.require_approval', $ruleset['require_approval'] ?? true))
                                   class="h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                            Validation admin obligatoire
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="ruleset[requires_shipping]" value="1"
                                   @checked(old('ruleset.requires_shipping', $ruleset['requires_shipping'] ?? false))
                                   class="h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                            Livraison requise
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="ruleset[allow_variants]" value="1"
                                   @checked(old('ruleset.allow_variants', $ruleset['allow_variants'] ?? false))
                                   class="h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                            Variantes autorisées
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <input name="ruleset[min_price]" type="number" step="0.01" min="0" placeholder="Prix min"
                                   value="{{ old('ruleset.min_price', $ruleset['min_price'] ?? '') }}"
                                   class="h-9 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <input name="ruleset[max_price]" type="number" step="0.01" min="0" placeholder="Prix max"
                                   value="{{ old('ruleset.max_price', $ruleset['max_price'] ?? '') }}"
                                   class="h-9 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                            <input name="ruleset[max_images]" type="number" min="1" max="20" placeholder="Img max"
                                   value="{{ old('ruleset.max_images', $ruleset['max_images'] ?? '') }}"
                                   class="h-9 rounded-lg border border-black/10 bg-surface px-2 text-xs">
                        </div>
                    </div>
                </fieldset>

                <div>
                    <x-ui.button type="submit" variant="primary">{{ $e ? 'Enregistrer' : 'Créer la catégorie' }}</x-ui.button>
                </div>
            </div>
        </form>

        @if ($e)
            <form method="POST" action="{{ route('admin.marketplace.categories.destroy', $e) }}" id="delete-cat-{{ $e->id }}"
                  onsubmit="return confirm('Supprimer cette catégorie ?')">
                @csrf @method('DELETE')
            </form>

            {{-- ============ CHAMPS PERSONNALISÉS ============ --}}
            <div class="rounded-2xl border border-black/10 bg-surface p-5" x-data="{ type: 'text' }">
                <h2 class="font-display text-base font-semibold text-ink">Champs personnalisés — {{ $e->name }}</h2>
                <p class="mt-1 text-xs text-ink-3">Les offres de cette catégorie héritent aussi des champs de ses catégories parentes.</p>

                @if ($e->fields->isNotEmpty())
                    <table class="mt-3 w-full text-sm">
                        <tbody class="divide-y divide-black/[0.06]">
                            @foreach ($e->fields as $f)
                                <tr>
                                    <td class="py-2">
                                        <span class="font-mono text-xs text-ink-3">{{ $f->key }}</span>
                                        <span class="ml-2 text-ink">{{ $f->label }}</span>
                                    </td>
                                    <td class="py-2 text-ink-2">{{ $fieldTypes[$f->type] ?? $f->type }}</td>
                                    <td class="py-2 text-xs text-ink-3">
                                        @if ($f->is_required) requis @endif
                                        @if ($f->is_filterable) · facette @endif
                                        @if ($f->is_variant_axis) · variante @endif
                                    </td>
                                    <td class="py-2 text-right">
                                        <form method="POST" action="{{ route('admin.marketplace.categories.fields.destroy', [$e, $f]) }}"
                                              onsubmit="return confirm('Supprimer ce champ ?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-rust hover:underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <form method="POST" action="{{ route('admin.marketplace.categories.fields.store', $e) }}" class="mt-4 grid gap-3 border-t border-black/[0.06] pt-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-sm">
                            <span class="text-ink-2">Libellé *</span>
                            <input name="label" required class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                        </label>
                        <label class="text-sm">
                            <span class="text-ink-2">Type</span>
                            <select name="type" x-model="type" class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 text-sm">
                                @foreach ($fieldTypes as $k => $lbl)
                                    <option value="{{ $k }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="text-sm">
                        <span class="text-ink-2">Clé machine (facultatif — déduite du libellé)</span>
                        <input name="key" placeholder="ex. matiere" class="mt-1 h-10 w-full rounded-xl border border-black/10 bg-surface px-3 font-mono text-xs">
                    </label>

                    <label class="text-sm" x-show="type === 'select' || type === 'multiselect'" x-cloak>
                        <span class="text-ink-2">Options (une par ligne)</span>
                        <textarea name="options_text" rows="3" class="mt-1 w-full rounded-xl border border-black/10 bg-surface px-3 py-2 text-sm"></textarea>
                    </label>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="is_required" value="1" class="h-4 w-4 rounded border-line-strong text-accent">Requis</label>
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="is_filterable" value="1" class="h-4 w-4 rounded border-line-strong text-accent">Facette de recherche</label>
                        <label class="inline-flex items-center gap-1.5" x-show="type === 'select'" x-cloak><input type="checkbox" name="is_variant_axis" value="1" class="h-4 w-4 rounded border-line-strong text-accent">Axe de variante</label>
                    </div>

                    <div>
                        <x-ui.button type="submit" variant="secondary">Ajouter le champ</x-ui.button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
