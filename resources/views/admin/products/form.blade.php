@extends('layouts.admin')

@php $isEdit = $product->exists; @endphp

@section('title', $isEdit ? 'Modifier — ' . $product->name : 'Nouveau produit')
@section('eyebrow', 'Catalogue')
@section('heading', $isEdit ? 'Modifier le produit' : 'Nouveau produit')

@section('actions')
    <x-ui.button :href="route('admin.products.index')" variant="secondary">← Retour</x-ui.button>
@endsection

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

@if ($errors->any())
    <x-ui.alert tone="error" class="mb-6">
        Le formulaire contient {{ $errors->count() }} erreur(s). Corrigez les champs signalés ci-dessous.
    </x-ui.alert>
@endif

<div class="grid gap-8 lg:grid-cols-[1fr_320px] lg:gap-10">
    {{-- ============ Colonne principale ============ --}}
    <div class="min-w-0 space-y-8">
    <form method="POST"
          action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
          enctype="multipart/form-data"
          x-data="{ type: @js(old('type', $product->type ?? 'digital')) }"
          class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- Général --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Général</p>
            <div class="grid gap-4">
                <x-ui.field name="name" label="Nom" :value="$product->name" :required="true" />
                <x-ui.field name="slug" label="Slug" :value="$product->slug" hint="Laisser vide pour générer automatiquement depuis le nom." />
                <x-ui.textarea name="description" label="Description" :rows="5">{{ $product->description }}</x-ui.textarea>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="category" label="Catégorie" :value="$product->category" />
                    <x-ui.field name="sub_category" label="Sous-catégorie" :value="$product->sub_category" />
                </div>
                <x-ui.field name="presentation_video" label="Vidéo de présentation (URL)" :value="$product->presentation_video" />
            </div>
        </section>

        {{-- Visuels (§13) --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5"
                 x-data="{
                     mainPreview: @js($product->main_image ?: null),
                     galleryPreviews: [],
                     pickMain(e) { const f = e.target.files[0]; this.mainPreview = f ? URL.createObjectURL(f) : @js($product->main_image ?: null); },
                     pickGallery(e) { this.galleryPreviews = Array.from(e.target.files).map(f => URL.createObjectURL(f)); },
                 }">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Visuels</p>

            {{-- Image principale --}}
            <div class="grid gap-4 sm:grid-cols-[140px_1fr] sm:items-start">
                <div class="aspect-square overflow-hidden rounded-xl border border-black/10 bg-ground/60">
                    <template x-if="mainPreview">
                        <img :src="mainPreview" alt="" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!mainPreview">
                        <div class="flex h-full w-full items-center justify-center text-ink-3">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="9" r="1.5"/></svg>
                        </div>
                    </template>
                </div>
                <div class="grid gap-2">
                    <label class="text-[13px] font-medium text-ink-2">Image principale</label>
                    <input type="file" name="main_image_file" accept="image/*" x-on:change="pickMain($event)"
                           class="block w-full text-sm text-ink-2 file:mr-3 file:rounded-lg file:border-0 file:bg-black/[0.06] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-ink hover:file:bg-black/[0.08]">
                    <p class="text-xs text-ink-3">JPG, PNG, WebP ou AVIF — 5 Mo max.</p>
                    @error('main_image_file')<p class="text-xs text-rust">{{ $message }}</p>@enderror
                    @if ($product->main_image)
                        <label class="mt-1 flex items-center gap-2 text-sm text-ink-2">
                            <input type="checkbox" name="remove_main_image" value="1" class="h-4 w-4 rounded border-black/20 bg-black/[0.04] text-accent">
                            Retirer l'image actuelle
                        </label>
                    @endif
                </div>
            </div>

            {{-- Galerie --}}
            <div class="mt-6 grid gap-3">
                <label class="text-[13px] font-medium text-ink-2">Galerie <span class="text-ink-3">(jusqu'à 8 images)</span></label>

                @if (! empty($product->gallery))
                    <div class="flex flex-wrap gap-3">
                        @foreach ($product->gallery as $img)
                            <label class="group relative block h-20 w-20 cursor-pointer overflow-hidden rounded-lg border border-black/10">
                                <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition group-has-[:checked]:opacity-30">
                                <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-ground/80 py-0.5 text-[10px] text-rust opacity-0 group-has-[:checked]:opacity-100">Retiré</span>
                                <input type="checkbox" name="gallery_remove[]" value="{{ $img }}" class="absolute right-1 top-1 h-4 w-4 rounded border-black/30 bg-ground/80 text-rust">
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-ink-3">Cochez une vignette pour la retirer à l'enregistrement.</p>
                @endif

                <input type="file" name="gallery_files[]" accept="image/*" multiple x-on:change="pickGallery($event)"
                       class="block w-full text-sm text-ink-2 file:mr-3 file:rounded-lg file:border-0 file:bg-black/[0.06] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-ink hover:file:bg-black/[0.08]">
                @error('gallery_files')<p class="text-xs text-rust">{{ $message }}</p>@enderror
                @error('gallery_files.*')<p class="text-xs text-rust">{{ $message }}</p>@enderror

                <div class="flex flex-wrap gap-3" x-show="galleryPreviews.length" x-cloak>
                    <template x-for="src in galleryPreviews" :key="src">
                        <img :src="src" alt="" class="h-20 w-20 rounded-lg border border-accent/40 object-cover">
                    </template>
                </div>
            </div>
        </section>

        {{-- Type & tarif --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Type &amp; tarif</p>
            <div class="grid gap-4">
                <x-ui.select name="type" label="Type de produit" x-model="type" :selected="$product->type ?? 'digital'" :required="true"
                    :options="['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service']" />
                @if ($isEdit)
                    <p class="-mt-2 text-xs text-ink-3">Changer le type d'un produit déjà vendu peut désaligner l'historique — à éviter.</p>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.field name="price" label="Prix" type="number" step="0.01" :value="$product->price" :required="true" />
                    <x-ui.field name="promotional_price" label="Prix promo" type="number" step="0.01" :value="$product->promotional_price" hint="< prix" />
                    <x-ui.field name="currency" label="Devise" :value="$product->currency ?? 'XOF'" :required="true" />
                </div>
            </div>
        </section>

        {{-- DIGITAL --}}
        <section x-show="type === 'digital'" x-cloak class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Produit digital</p>
            <div class="grid gap-4">
                <x-ui.field name="file_path" label="Fichier livré (chemin protégé)" :value="$product->file_path" hint="Requis pour un produit digital. Ex : products/mon-ebook.pdf" />
                <label class="flex items-start gap-3 rounded-xl border border-black/10 bg-surface p-3.5 text-sm">
                    <input type="hidden" name="is_affiliable" value="0">
                    <input type="checkbox" name="is_affiliable" value="1" @checked(old('is_affiliable', $product->is_affiliable))
                           class="mt-0.5 h-4 w-4 rounded border-black/20 bg-black/[0.04] text-accent">
                    <span>
                        <span class="font-medium text-ink">Éligible au programme d'affiliation</span>
                        <span class="block text-xs text-ink-3">§4 — réservé aux produits digitaux. Déclenche les commissions 15 · 10 · 5 %.</span>
                    </span>
                </label>
            </div>
        </section>

        {{-- PHYSIQUE --}}
        <section x-show="type === 'physical'" x-cloak class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Produit physique</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field name="sku" label="SKU" :value="$product->sku" hint="Requis, unique." />
                <x-ui.field name="weight" label="Poids (kg)" type="number" step="0.01" :value="$product->weight" />
                @unless ($isEdit)
                    <x-ui.field name="stock_available" label="Stock initial" type="number" :value="$product->stock_available ?? 0" />
                @endunless
            </div>
            @if ($isEdit)
                <p class="mt-3 text-xs text-ink-3">
                    Le stock se gère dans le panneau « Stock » ci-contre.
                    @if ($product->variants->isNotEmpty())
                        Ce produit a {{ $product->variants->count() }} variante(s) : le stock réel est celui de chaque variante (section « Variantes » ci-dessous).
                    @endif
                </p>
            @endif
        </section>

        {{-- SERVICE --}}
        <section x-show="type === 'service'" x-cloak class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Prestation / service</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field name="delivery_mode" label="Mode" :value="$product->delivery_mode" hint="présentiel / visioconférence" />
                <x-ui.field name="location" label="Lieu" :value="$product->location" />
                <x-ui.field name="meeting_link" label="Lien de visioconférence" :value="$product->meeting_link" />
            </div>
            <p class="mt-3 text-xs text-ink-3">
                @if ($isEdit)
                    Le calendrier (durée, jours &amp; horaires, capacité, pauses, jours indisponibles) se gère dans la section « Calendrier » ci-dessous.
                @else
                    Enregistrez d'abord le service, puis définissez son calendrier (durée, horaires, capacité).
                @endif
            </p>
        </section>

        {{-- Publication --}}
        <section class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Publication</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select name="status" label="Statut" :selected="$product->status ?? 'draft'" :required="true"
                    :options="['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé']" />
                <label class="flex items-center gap-3 self-end rounded-xl border border-black/10 bg-surface px-3.5 py-2.5 text-sm">
                    <input type="hidden" name="is_available" value="0">
                    <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $product->is_available ?? true))
                           class="h-4 w-4 rounded border-black/20 bg-black/[0.04] text-accent">
                    Disponible à la vente
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit" variant="primary">{{ $isEdit ? 'Enregistrer' : 'Créer le produit' }}</x-ui.button>
            @if ($isEdit)
                <a href="{{ route('admin.products.show', $product) }}" class="text-sm text-ink-2 hover:text-ink">Voir la fiche</a>
            @endif
            <div class="ml-auto flex items-center gap-3">
                @if ($isEdit && $product->status !== 'archived')
                    <button form="archive-product" class="text-sm text-ink-3 hover:text-ink">Archiver</button>
                @endif
                @if ($isEdit)
                    <button form="delete-product"
                            class="inline-flex h-10 items-center rounded-xl border border-rust/40 px-4 text-sm font-semibold text-rust transition-colors hover:bg-rust/10">
                        Supprimer
                    </button>
                @endif
            </div>
        </div>
    </form>

    @if ($isEdit && $product->status !== 'archived')
        <form id="archive-product" method="POST" action="{{ route('admin.products.destroy', $product) }}" class="hidden"
              onsubmit="return confirm('Archiver ce produit ? Il ne sera plus visible en boutique.')">
            @csrf @method('DELETE')
        </form>
    @endif

    @if ($isEdit)
        <form id="delete-product" method="POST" action="{{ route('admin.products.delete', $product) }}" class="hidden"
              onsubmit="return confirm('Supprimer définitivement « {{ $product->name }} » ? Cette action retire le produit du catalogue.')">
            @csrf @method('DELETE')
        </form>
    @endif

    @if ($isEdit && $product->type === 'physical')
        @include('admin.products.partials.variants')
    @endif
    @if ($isEdit && $product->type === 'service')
        @include('admin.products.partials.availability')
    @endif
    </div>

    {{-- ============ Colonne latérale : STOCK (édition physique) ============ --}}
    <div class="space-y-6 lg:sticky lg:top-20 lg:self-start">
        @if ($isEdit && $product->type === 'physical')
            <div id="stock" class="scroll-mt-24 rounded-2xl border border-black/10 bg-surface p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Stock</p>

                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-xl font-bold text-ink">{{ (int) $product->stock_available }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-ink-3">Dispo.</p>
                    </div>
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-xl font-bold text-ink-2">{{ (int) $product->stock_reserved }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-ink-3">Réservé</p>
                    </div>
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-xl font-bold text-ink-2">{{ (int) $product->stock_sold }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-ink-3">Vendu</p>
                    </div>
                </div>

                @if ($product->variants->isNotEmpty())
                    <p class="mt-3 text-xs text-ink-3">Ce produit a des variantes — ajustez le stock par variante dans la section « Variantes ».</p>
                @else
                    <form method="POST" action="{{ route('admin.products.stock.adjust', $product) }}" class="mt-4 grid gap-3">
                        @csrf
                        <x-ui.field name="stock_available" label="Nouveau stock disponible" type="number"
                            :value="(int) $product->stock_available" :required="true" />
                        <x-ui.field name="reason" label="Motif" placeholder="Réapprovisionnement, inventaire…" />
                        <x-ui.button type="submit" variant="secondary" :full="true">Ajuster le stock</x-ui.button>
                    </form>
                @endif
            </div>

            @if ($movements->isNotEmpty())
                <div class="rounded-2xl border border-black/10 bg-surface p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Historique des mouvements</p>
                    @php
                        $moveLabels = [
                            'reserved' => 'Réservé', 'released' => 'Libéré', 'sold' => 'Vendu',
                            'returned' => 'Retour', 'restocked' => 'Réappro.', 'adjustment' => 'Ajustement',
                        ];
                        $moveSign = ['reserved' => '−', 'sold' => '', 'released' => '+', 'returned' => '+', 'restocked' => '+', 'adjustment' => '±'];
                    @endphp
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($movements as $mv)
                            <li class="flex items-baseline justify-between gap-3 border-b border-black/[0.05] pb-2 last:border-0 last:pb-0">
                                <div>
                                    <span class="text-ink">{{ $moveLabels[$mv->type] ?? $mv->type }}</span>
                                    <span class="text-ink-3"> {{ $moveSign[$mv->type] ?? '' }}{{ $mv->quantity }}</span>
                                    @if ($mv->reason)<span class="block text-xs text-ink-3">{{ $mv->reason }}</span>@endif
                                    @if ($mv->admin)<span class="block text-xs text-ink-3">par {{ $mv->admin->first_name }} {{ $mv->admin->last_name }}</span>@endif
                                </div>
                                <span class="shrink-0 text-xs text-ink-3">{{ $mv->created_at->format('d/m/y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @elseif (! $isEdit)
            <div class="rounded-2xl border border-dashed border-black/12 bg-surface p-5 text-sm text-ink-3">
                Enregistrez le produit, puis gérez ici son stock (physique) ou sa disponibilité (service).
            </div>
        @endif
    </div>
</div>
@endsection
