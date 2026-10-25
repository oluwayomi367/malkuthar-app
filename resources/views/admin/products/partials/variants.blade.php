@php
    $optFields = ['taille' => 'Taille', 'couleur' => 'Couleur', 'modele' => 'Modèle', 'format' => 'Format', 'autre' => 'Autre'];

    $initialRows = $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name,
        'sku' => $v->sku,
        'price' => $v->price !== null ? (float) $v->price : '',
        'is_active' => (bool) $v->is_active,
        'image' => $v->image,
        '_preview' => null,
        'remove_image' => false,
        'options' => array_merge(['taille' => '', 'couleur' => '', 'modele' => '', 'format' => '', 'autre' => ''], (array) $v->options),
    ])->values();
@endphp

<section class="rounded-2xl border border-night-line bg-night-2 p-5" id="variantes">
    <div class="flex items-center justify-between">
        <h2 class="font-sans text-base font-semibold text-night-ink">Variantes <span class="text-night-ink-2">(§12)</span></h2>
        <span class="text-xs text-night-ink-2">{{ $product->variants->count() }} variante{{ $product->variants->count() > 1 ? 's' : '' }}</span>
    </div>
    <p class="mt-1 text-xs text-night-ink-2">Taille, couleur, modèle, format… Chaque variante a son SKU, son prix et son image. Le stock se règle plus bas.</p>

    <form method="POST" action="{{ route('admin.products.variants.sync', $product) }}" enctype="multipart/form-data"
          class="mt-4" x-data="{ rows: {{ \Illuminate\Support\Js::from($initialRows) }} }">
        @csrf @method('PUT')

        <template x-if="rows.length === 0">
            <p class="rounded-lg border border-dashed border-night-line px-3 py-5 text-center text-xs text-night-ink-2">Aucune variante — ce produit se vend tel quel.</p>
        </template>

        <div class="space-y-3">
            <template x-for="(row, i) in rows" :key="i">
                <div class="rounded-xl border border-night-line p-3">
                    <template x-if="row.id"><input type="hidden" :name="`variants[${i}][id]`" :value="row.id"></template>

                    <div class="flex items-start gap-3">
                        {{-- Vignette --}}
                        <label class="relative block h-16 w-16 shrink-0 cursor-pointer overflow-hidden rounded-lg border border-night-line bg-night">
                            <template x-if="row._preview || (row.image && !row.remove_image)">
                                <img :src="row._preview || row.image" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!(row._preview || (row.image && !row.remove_image))">
                                <span class="flex h-full w-full items-center justify-center text-xs text-night-ink-2">Image</span>
                            </template>
                            <input type="file" accept="image/*" class="sr-only" :name="`variants[${i}][image_file]`"
                                   x-on:change="row._preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; row.remove_image = false">
                        </label>

                        <div class="min-w-0 flex-1 space-y-2">
                            <input type="text" required :name="`variants[${i}][name]`" x-model="row.name" placeholder="Nom de la variante — ex. Rouge / M"
                                   class="h-9 w-full rounded-lg border border-night-line bg-night-2 px-2.5 text-sm text-night-ink">

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($optFields as $key => $label)
                                    <input type="text" :name="`variants[${i}][options][{{ $key }}]`" x-model="row.options.{{ $key }}" placeholder="{{ $label }}"
                                           class="h-8 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink placeholder:text-night-ink-2/70">
                                @endforeach
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" :name="`variants[${i}][sku]`" x-model="row.sku" placeholder="SKU"
                                       class="h-8 rounded-lg border border-night-line bg-night-2 px-2 text-sm font-mono text-night-ink placeholder:text-night-ink-2/70">
                                <input type="number" step="0.01" min="0" :name="`variants[${i}][price]`" x-model="row.price" placeholder="Prix (défaut produit)"
                                       class="h-8 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink placeholder:text-night-ink-2/70">
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                <template x-if="!row.id">
                                    <label class="flex items-center gap-1.5 text-night-ink-2">
                                        Stock initial
                                        <input type="number" min="0" :name="`variants[${i}][stock_available]`" value="0"
                                               class="h-7 w-16 rounded-md border border-night-line bg-night-2 px-1.5 text-night-ink">
                                    </label>
                                </template>
                                <label class="flex items-center gap-1.5 text-night-ink-2">
                                    <input type="hidden" :name="`variants[${i}][is_active]`" value="0">
                                    <input type="checkbox" value="1" :name="`variants[${i}][is_active]`" x-model="row.is_active"
                                           class="h-4 w-4 rounded border-night-ink-2/60 text-accent-2">
                                    Active
                                </label>
                                <template x-if="row.image">
                                    <label class="flex items-center gap-1.5 text-night-ink-2">
                                        <input type="checkbox" value="1" :name="`variants[${i}][remove_image]`" x-model="row.remove_image" class="h-4 w-4 rounded border-night-ink-2/60 text-rust">
                                        Retirer l'image
                                    </label>
                                </template>
                                <button type="button" x-on:click="rows.splice(i, 1)" class="ml-auto text-xs text-rust hover:underline">Retirer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="button" class="text-xs font-semibold text-accent-2 hover:underline"
                    x-on:click="rows.push({ id: null, name: '', sku: '', price: '', is_active: true, image: null, _preview: null, remove_image: false, options: { taille:'', couleur:'', modele:'', format:'', autre:'' } })">
                + Ajouter une variante
            </button>
            <x-ui.button type="submit" variant="secondary">Enregistrer les variantes</x-ui.button>
        </div>
    </form>

    {{-- Stock par variante (formulaires indépendants) --}}
    @if ($product->variants->isNotEmpty())
        <div class="mt-6 border-t border-night-line pt-4">
            <h2 class="font-sans text-base font-semibold text-night-ink">Stock par variante</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-night-ink-2">
                        <tr>
                            <th class="py-2 pr-3 font-semibold">Variante</th>
                            <th class="px-2 py-2 text-center font-semibold">Dispo.</th>
                            <th class="px-2 py-2 text-center font-semibold">Réservé</th>
                            <th class="px-2 py-2 text-center font-semibold">Vendu</th>
                            <th class="py-2 pl-2 font-semibold">Ajuster</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-night-line">
                        @foreach ($product->variants as $v)
                            <tr>
                                <td class="py-2 pr-3">
                                    <span class="text-night-ink">{{ $v->name }}</span>
                                    @if ($v->sku)<span class="block font-mono text-xs text-night-ink-2">{{ $v->sku }}</span>@endif
                                </td>
                                <td class="px-2 py-2 text-center font-semibold {{ (int) $v->stock_available <= 3 ? 'text-rust' : 'text-night-ink' }}">{{ (int) $v->stock_available }}</td>
                                <td class="px-2 py-2 text-center text-night-ink-2">{{ (int) $v->stock_reserved }}</td>
                                <td class="px-2 py-2 text-center text-night-ink-2">{{ (int) $v->stock_sold }}</td>
                                <td class="py-2 pl-2">
                                    <form method="POST" action="{{ route('admin.products.variants.stock', $v) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        <input type="number" name="stock_available" min="0" value="{{ (int) $v->stock_available }}"
                                               class="h-8 w-16 rounded-md border border-night-line bg-night-2 px-1.5 text-sm text-night-ink">
                                        <input type="text" name="reason" placeholder="Motif"
                                               class="h-8 w-24 rounded-md border border-night-line bg-night-2 px-1.5 text-sm text-night-ink placeholder:text-night-ink-2/70">
                                        <button class="h-8 rounded-md border border-night-line px-2.5 text-xs font-semibold text-night-ink hover:bg-night-line/50">OK</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
