<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * §73-§74 — vitrine publique. Rendu via Inertia/React : on vérifie les
 * props transmises au composant, pas du HTML rendu côté serveur (React
 * hydrate côté client, sans SSR).
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_published_available_products(): void
    {
        Product::factory()->digitalAffiliable()->create(['name' => 'Ebook Visible', 'status' => 'published', 'is_available' => true]);
        Product::factory()->create(['name' => 'Brouillon', 'status' => 'draft']);
        Product::factory()->create(['name' => 'Rupture', 'status' => 'published', 'is_available' => false]);

        $this->get('/boutique')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Ebook Visible'));
    }

    public function test_home_page_shows_new_arrivals_without_the_full_catalog(): void
    {
        Product::factory()->digitalAffiliable()->create(['name' => 'Ebook Catalogue', 'status' => 'published', 'is_available' => true]);
        Product::factory()->digitalAffiliable()->create(['name' => 'Ebook Brouillon', 'status' => 'draft', 'is_available' => true]);

        // L'accueil montre des rayons (nouveautés…) mais pas la grille paginée de /boutique.
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home')
                ->missing('products')
                ->has('newArrivals', 1)
                ->where('newArrivals.0.name', 'Ebook Catalogue')
                ->where('newArrivals.0.kind', 'product'));

        $this->get('/boutique')
            ->assertInertia(fn (Assert $page) => $page->where('products.data.0.name', 'Ebook Catalogue'));
    }

    public function test_type_filter_restricts_the_listing(): void
    {
        Product::factory()->create(['name' => 'Un Service', 'type' => 'service', 'status' => 'published']);
        Product::factory()->digitalAffiliable()->create(['name' => 'Un Ebook', 'status' => 'published']);

        $this->get('/boutique?type=service')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Un Service'));
    }

    public function test_product_page_loads_for_published_and_404s_otherwise(): void
    {
        $product = Product::factory()->digitalAffiliable()->create([
            'slug' => 'formation-x', 'status' => 'published', 'name' => 'Formation X',
        ]);
        Product::factory()->create(['slug' => 'cache', 'status' => 'draft']);

        // §74 — les données structurées JSON-LD sont émises par le composant
        // React (voir Pages/Shop/Product/Digital.jsx), pas par le serveur :
        // on vérifie ici que le produit attendu est bien celui transmis.
        $this->get('/produits/formation-x')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Product/Digital')
                ->where('product.name', 'Formation X')
                ->where('product.slug', $product->slug));

        $this->get('/produits/cache')->assertNotFound();
        $this->get('/produits/inexistant')->assertNotFound();
    }

    public function test_catalog_extracts_dynamic_colors_and_filters_by_color(): void
    {
        $prodNoir = Product::factory()->physical()->create(['name' => 'Monture Ébène', 'status' => 'published', 'is_available' => true]);
        \App\Models\ProductVariant::create([
            'product_id' => $prodNoir->id,
            'name' => 'Noir',
            'sku' => 'MONT-NOIR',
            'stock_available' => 4,
            'is_active' => true,
            'options' => ['couleur' => 'Noir'],
        ]);

        $prodBlanc = Product::factory()->physical()->create(['name' => 'Monture Ivoire', 'status' => 'published', 'is_available' => true]);
        \App\Models\ProductVariant::create([
            'product_id' => $prodBlanc->id,
            'name' => 'Blanc',
            'sku' => 'MONT-BLANC',
            'stock_available' => 2,
            'is_active' => true,
            'options' => ['couleur' => 'Blanc'],
        ]);

        $res = $this->get('/boutique')->assertOk();
        $res->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')
            ->has('availableColors', 2)
            ->where('availableColors', ['Noir', 'Blanc'])
        );

        $filterRes = $this->get('/boutique?couleur=Noir')->assertOk();
        $filterRes->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Monture Ébène')
        );
    }
}
