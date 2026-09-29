<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
    }

    private function liveOffer(array $attrs = [], ?VendorProfile $vendor = null): MarketplaceOffer
    {
        return MarketplaceOffer::factory()
            ->approved()
            ->for($vendor ?? VendorProfile::factory()->active(), 'vendor')
            ->create($attrs);
    }

    public function test_shop_is_404_while_the_module_is_disabled(): void
    {
        \App\Models\Setting::put('marketplace.enabled', '0');

        $this->get('/place-de-marche')->assertNotFound();
    }

    public function test_shop_lists_only_publicly_visible_offers(): void
    {
        $visible = $this->liveOffer(['title' => 'Coffret visible']);
        MarketplaceOffer::factory()->create(['title' => 'Brouillon caché']);            // draft
        MarketplaceOffer::factory()->pendingReview()->create(['title' => 'En revue']);   // pending
        $suspendedVendor = VendorProfile::factory()->suspended()->create();
        $this->liveOffer(['title' => 'Vendeur suspendu'], $suspendedVendor);

        $res = $this->get('/place-de-marche')->assertOk();
        $res->assertSee('Coffret visible');
        $res->assertDontSee('Brouillon caché');
        $res->assertDontSee('En revue');
        $res->assertDontSee('Vendeur suspendu');
    }

    public function test_category_filter_includes_the_whole_subtree(): void
    {
        $root = MarketplaceCategory::factory()->create(['name' => 'Maison', 'slug' => 'maison']);
        $child = MarketplaceCategory::factory()->childOf($root)->create();

        $this->liveOffer(['title' => 'Objet racine', 'category_id' => $root->id]);
        $this->liveOffer(['title' => 'Objet enfant', 'category_id' => $child->id]);
        $this->liveOffer(['title' => 'Objet ailleurs']);

        $res = $this->get('/place-de-marche?categorie=maison')->assertOk();
        $res->assertSee('Objet racine');
        $res->assertSee('Objet enfant');
        $res->assertDontSee('Objet ailleurs');
    }

    public function test_type_and_price_filters(): void
    {
        $this->liveOffer(['title' => 'Service cher', 'type' => 'service', 'price' => 50000]);
        $this->liveOffer(['title' => 'Produit pas cher', 'type' => 'physical', 'price' => 8000]);
        $this->liveOffer(['title' => 'Produit promo', 'type' => 'physical', 'price' => 30000, 'sale_price' => 9000]);

        $this->get('/place-de-marche?type=service')->assertOk()
            ->assertSee('Service cher')->assertDontSee('Produit pas cher');

        $this->get('/place-de-marche?prix_max=10000')->assertOk()
            ->assertSee('Produit pas cher')
            ->assertSee('Produit promo')          // prix effectif 9000
            ->assertDontSee('Service cher');
    }

    public function test_custom_field_facet_filters_the_listing(): void
    {
        $category = MarketplaceCategory::factory()->create(['slug' => 'bois']);
        MarketplaceCategoryField::factory()->key('essence')->select([
            ['value' => 'teck', 'label' => 'Teck'],
            ['value' => 'ebene', 'label' => 'Ébène'],
        ])->for($category, 'category')->create();

        $this->liveOffer(['title' => 'Table en teck', 'category_id' => $category->id, 'custom_fields' => ['essence' => 'teck']]);
        $this->liveOffer(['title' => 'Table en ébène', 'category_id' => $category->id, 'custom_fields' => ['essence' => 'ebene']]);

        $res = $this->get('/place-de-marche?categorie=bois&f[essence]=teck')->assertOk();
        $res->assertSee('Table en teck');
        $res->assertDontSee('Table en ébène');
    }

    public function test_offer_detail_page(): void
    {
        $offer = $this->liveOffer(['title' => 'Ma belle offre', 'summary' => 'Une accroche']);

        $this->get(route('marketplace.shop.offer', $offer->slug))
            ->assertOk()
            ->assertSee('Ma belle offre')
            ->assertSee($offer->vendor->display_name);

        $draft = MarketplaceOffer::factory()->create();
        $this->get(route('marketplace.shop.offer', $draft->slug))->assertNotFound();
    }

    public function test_vendor_page_lists_only_that_vendors_visible_offers(): void
    {
        $vendor = VendorProfile::factory()->active()->create(['display_name' => 'Atelier Bois', 'slug' => 'atelier-bois']);
        $this->liveOffer(['title' => 'Offre A du vendeur'], $vendor);
        MarketplaceOffer::factory()->for($vendor, 'vendor')->create(['title' => 'Brouillon du vendeur']);
        $this->liveOffer(['title' => "Offre d'un autre"]);

        $res = $this->get(route('marketplace.shop.vendor', 'atelier-bois'))->assertOk();
        $res->assertSee('Offre A du vendeur');
        $res->assertDontSee('Brouillon du vendeur');
        $res->assertDontSee("Offre d'un autre");

        $this->get(route('marketplace.shop.vendor', 'inconnu'))->assertNotFound();
    }

    public function test_marketplace_sitemap_is_separate_from_the_owner_sitemap(): void
    {
        $offer = $this->liveOffer();
        \App\Models\Product::factory()->create(['slug' => 'produit-maison', 'status' => 'published', 'is_available' => true]);

        // Le sitemap propriétaire ne contient JAMAIS d'offre de vendeur.
        $owner = $this->get('/sitemap.xml')->assertOk();
        $owner->assertSee('/produits/produit-maison');
        $owner->assertDontSee('/place-de-marche/o/'.$offer->slug);

        // Le sitemap marketplace ne contient QUE des offres de vendeurs.
        $mp = $this->get('/sitemap-marketplace.xml')->assertOk();
        $mp->assertSee('/place-de-marche/o/'.$offer->slug);
        $mp->assertDontSee('/produits/produit-maison');
    }

    public function test_marketplace_sitemap_is_404_when_disabled(): void
    {
        \App\Models\Setting::put('marketplace.enabled', '0');
        $this->get('/sitemap-marketplace.xml')->assertNotFound();
    }

    public function test_owner_shop_and_marketplace_shop_never_share_listings(): void
    {
        \App\Models\Product::factory()->create([
            'name' => 'Produit du proprietaire', 'slug' => 'prop-1',
            'status' => 'published', 'is_available' => true,
        ]);
        $vendorOffer = $this->liveOffer(['title' => 'Offre du vendeur']);

        // Boutique propriétaire : aucune offre de vendeur.
        $this->get('/boutique')->assertOk()
            ->assertSee('Produit du proprietaire')
            ->assertDontSee('Offre du vendeur');

        // Place de marché : aucun produit propriétaire.
        $this->get('/place-de-marche')->assertOk()
            ->assertSee('Offre du vendeur')
            ->assertDontSee('Produit du proprietaire');
    }

    public function test_marketplace_extracts_dynamic_colors_and_filters_by_color(): void
    {
        $offerNoir = $this->liveOffer(['title' => 'Tunique Noire', 'type' => 'physical']);
        \App\Models\Marketplace\MarketplaceOfferVariant::create([
            'offer_id' => $offerNoir->id,
            'name' => 'Tunique Noire L',
            'sku' => 'TUN-NOIR-L',
            'stock' => 5,
            'is_active' => true,
            'option_values' => ['couleur' => 'Noir', 'taille' => 'L'],
        ]);

        $offerDore = $this->liveOffer(['title' => 'Bague Dorée', 'type' => 'physical']);
        \App\Models\Marketplace\MarketplaceOfferVariant::create([
            'offer_id' => $offerDore->id,
            'name' => 'Bague Dorée',
            'sku' => 'BAG-DOR',
            'stock' => 3,
            'is_active' => true,
            'option_values' => ['couleur' => 'Doré'],
        ]);

        $res = $this->get('/place-de-marche')->assertOk();
        $res->assertInertia(fn ($page) => $page
            ->component('Marketplace/Shop')
            ->has('availableColors', 2)
            ->where('availableColors', ['Noir', 'Doré'])
        );

        // Filtrage par couleur
        $filterRes = $this->get('/place-de-marche?couleur=Noir')->assertOk();
        $filterRes->assertSee('Tunique Noire');
        $filterRes->assertDontSee('Bague Dorée');
    }
}
