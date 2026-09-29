<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Services\Marketplace\MarketplaceCheckoutService;
use App\Services\Marketplace\MarketplaceCommissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferCommissionTest extends TestCase
{
    use InteractsWithMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
    }

    public function test_offer_with_specific_commission_rate_overrides_vendor_and_category_rates(): void
    {
        $vendor = $this->activeVendor(['commission_rate_override' => 0.20]); // 20% sur le vendeur
        $category = MarketplaceCategory::factory()->create(['commission_rate' => 0.18]); // 18% sur la catégorie

        // Offre configurée avec 12% spécifique par l'admin
        $offer = MarketplaceOffer::factory()->forVendor($vendor)->create([
            'category_id' => $category->id,
            'type' => 'digital',
            'commission_rate' => 0.12,
        ]);

        $resolver = app(MarketplaceCommissionResolver::class);
        $res = $resolver->resolve($vendor, $category, 'digital', null, $offer);

        $this->assertSame(0.12, $res['rate']);
        $this->assertSame('offer_override', $res['source']);

        $fee = $resolver->computeFee($vendor, $category, 'digital', 10000, null, $offer);
        $this->assertSame(1200.0, $fee['amount']);
        $this->assertSame('offer_override', $fee['source']);
    }

    public function test_offer_without_specific_commission_rate_falls_back_to_vendor_rate(): void
    {
        $vendor = $this->activeVendor(['commission_rate_override' => 0.20]);
        $category = MarketplaceCategory::factory()->create(['commission_rate' => 0.18]);

        // Offre sans surcharge
        $offer = MarketplaceOffer::factory()->forVendor($vendor)->create([
            'category_id' => $category->id,
            'type' => 'digital',
            'commission_rate' => null,
        ]);

        $resolver = app(MarketplaceCommissionResolver::class);
        $res = $resolver->resolve($vendor, $category, 'digital', null, $offer);

        $this->assertSame(0.20, $res['rate']);
        $this->assertSame('vendor_override', $res['source']);
    }

    public function test_admin_can_set_and_clear_offer_commission_rate(): void
    {
        $admin = $this->admin();
        $vendor = $this->activeVendor();
        $offer = MarketplaceOffer::factory()->forVendor($vendor)->create([
            'commission_rate' => null,
        ]);

        // Admin définit 14.5% sur ce produit
        $this->actingAs($admin)
            ->put(route('admin.marketplace.offers.commission', $offer), [
                'commission_rate_percent' => '14.5',
            ])
            ->assertSessionHas('success');

        $offer->refresh();
        $this->assertSame(0.145, (float) $offer->commission_rate);

        // Admin réinitialise pour repasser au barème standard
        $this->actingAs($admin)
            ->put(route('admin.marketplace.offers.commission', $offer), [
                'commission_rate_percent' => '',
            ])
            ->assertSessionHas('success');

        $offer->refresh();
        $this->assertNull($offer->commission_rate);
    }

    public function test_added_product_is_not_publicly_visible_until_admin_approves(): void
    {
        $admin = $this->admin();
        $vendor = $this->activeVendor();
        $category = MarketplaceCategory::factory()->create();

        // Le vendeur crée une offre soumise à modération
        $offer = MarketplaceOffer::factory()->forVendor($vendor)->create([
            'category_id' => $category->id,
            'title' => 'Livre Alchimie Mystique',
            'status' => 'pending_review',
            'is_online' => false,
        ]);

        // L'offre n'apparaît PAS sur la boutique publique
        $this->assertFalse(MarketplaceOffer::query()->publiclyVisible()->where('id', $offer->id)->exists());

        // La page publique de l'offre renvoie 404 tant que pas approuvée
        $this->get(route('marketplace.shop.offer', $offer->slug))->assertNotFound();

        // L'administrateur approuve manuellement l'offre
        $this->actingAs($admin)
            ->post(route('admin.marketplace.offers.approve', $offer), ['note' => 'Contenu de haute qualité vérifié.'])
            ->assertRedirect(route('admin.marketplace.offers.index'));

        $offer->refresh();
        $this->assertSame('approved', $offer->status);
        $this->assertTrue($offer->is_online);

        // Désormais elle est visible publiquement sur la boutique
        $this->assertTrue(MarketplaceOffer::query()->publiclyVisible()->where('id', $offer->id)->exists());
        $this->get(route('marketplace.shop.offer', $offer->slug))->assertOk();
    }
}
