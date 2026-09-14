<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §73-§74 — vitrine publique.
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_published_available_products(): void
    {
        $visible = Product::factory()->digitalAffiliable()->create([
            'name' => 'Ebook Visible', 'status' => 'published', 'is_available' => true,
        ]);
        Product::factory()->create(['name' => 'Brouillon', 'status' => 'draft']);
        Product::factory()->create(['name' => 'Rupture', 'status' => 'published', 'is_available' => false]);

        $this->get('/boutique')
            ->assertOk()
            ->assertSee('Ebook Visible')
            ->assertDontSee('Brouillon')
            ->assertDontSee('Rupture');
    }

    public function test_home_page_is_a_landing_not_a_catalog(): void
    {
        Product::factory()->digitalAffiliable()->create(['name' => 'Ebook Catalogue', 'status' => 'published', 'is_available' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Explorer la boutique')
            ->assertSee(route('catalog.index'), false)
            // l'accueil ne liste aucun produit : ils vivent sur /boutique.
            ->assertDontSee('Ebook Catalogue');

        $this->get('/boutique')->assertSee('Ebook Catalogue');
    }

    public function test_type_filter_restricts_the_listing(): void
    {
        Product::factory()->create(['name' => 'Un Service', 'type' => 'service', 'status' => 'published']);
        Product::factory()->digitalAffiliable()->create(['name' => 'Un Ebook', 'status' => 'published']);

        $this->get('/boutique?type=service')
            ->assertOk()
            ->assertSee('Un Service')
            ->assertDontSee('Un Ebook');
    }

    public function test_product_page_loads_for_published_and_404s_otherwise(): void
    {
        $product = Product::factory()->digitalAffiliable()->create([
            'slug' => 'formation-x', 'status' => 'published', 'name' => 'Formation X',
        ]);
        Product::factory()->create(['slug' => 'cache', 'status' => 'draft']);

        $this->get('/produits/formation-x')
            ->assertOk()
            ->assertSee('Formation X')
            ->assertSee('application/ld+json', false); // §74 données structurées

        $this->get('/produits/cache')->assertNotFound();
        $this->get('/produits/inexistant')->assertNotFound();
    }
}
