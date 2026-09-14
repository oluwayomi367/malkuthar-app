@if ($products->isEmpty())
    <x-ui.empty-state
        title="Aucun produit ne correspond"
        text="Essayez de retirer un filtre ou de changer de catégorie."
    >
        <x-slot:cta>
            <x-ui.button variant="secondary" :href="route('catalog.index')">Réinitialiser</x-ui.button>
        </x-slot:cta>
    </x-ui.empty-state>
@else
    <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($products as $product)
            <x-commerce.product-card :product="$product" />
        @endforeach
    </div>

    <x-ui.pagination :paginator="$products" />
@endif
