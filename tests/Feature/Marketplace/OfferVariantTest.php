<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOfferVariant;
use App\Models\Marketplace\VendorProfile;
use App\Services\Marketplace\MarketplaceCartService;
use App\Services\Marketplace\MarketplaceCheckoutService;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OfferVariantTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    private VendorProfile $vendor;
    private MarketplaceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
        Notification::fake();
        $this->vendor = VendorProfile::factory()->active()->create();
        $this->category = MarketplaceCategory::factory()->types(['physical'])->create();
    }

    private function offerPayload(array $overrides = []): array
    {
        // `variants` remplace entièrement le défaut (pas de merge récursif de liste).
        $variants = $overrides['variants'] ?? [
            ['option_values' => ['Couleur' => 'Écru', 'Taille' => 'M'], 'stock' => 6, 'sku' => 'JUTE-ECRU-M', 'is_active' => 1],
            ['option_values' => ['Couleur' => 'Indigo', 'Taille' => 'M'], 'stock' => 4, 'sku' => 'JUTE-IND-M', 'price_override' => 13500, 'is_active' => 1],
        ];
        unset($overrides['variants']);

        return array_replace_recursive([
            'category_id' => $this->category->id,
            'type' => 'physical',
            'title' => 'Sac en toile de jute',
            'description' => str_repeat('Un sac solide, cousu main dans notre atelier. ', 4),
            'price' => 12000,
            'detail' => ['stock' => 0],
        ], $overrides) + ['variants' => $variants];
    }

    public function test_vendor_creates_an_offer_with_variants(): void
    {
        $this->actingAs($this->vendor->user)
            ->post(route('vendor.offers.store'), $this->offerPayload())
            ->assertRedirect();

        $offer = MarketplaceOffer::first();
        $this->assertTrue($offer->hasVariants());
        $this->assertSame(2, $offer->variants()->count());

        $indigo = $offer->variants()->where('sku', 'JUTE-IND-M')->first();
        $this->assertSame('Indigo / M', $indigo->name);
        $this->assertSame(['Couleur' => 'Indigo', 'Taille' => 'M'], $indigo->option_values);
        $this->assertSame('13500.00', (string) $indigo->price_override);
        $this->assertSame(4, $indigo->stock);
        $this->assertTrue($offer->physical->has_variants);
    }

    public function test_duplicate_variant_skus_are_rejected(): void
    {
        $this->actingAs($this->vendor->user)->post(route('vendor.offers.store'), $this->offerPayload([
            'variants' => [
                ['option_values' => ['Couleur' => 'A'], 'stock' => 1, 'sku' => 'DUP', 'is_active' => 1],
                ['option_values' => ['Couleur' => 'B'], 'stock' => 1, 'sku' => 'DUP', 'is_active' => 1],
            ],
        ]))->assertSessionHasErrors('variants');
    }

    public function test_main_image_upload_is_stored(): void
    {
        $this->actingAs($this->vendor->user)->post(route('vendor.offers.store'), array_merge(
            $this->offerPayload(['variants' => []]),
            ['detail' => ['stock' => 5], 'main_image' => UploadedFile::fake()->image('sac.jpg', 400, 400)],
        ))->assertRedirect();

        $offer = MarketplaceOffer::first();
        $this->assertNotNull($offer->main_image_path);
        $this->assertStringStartsWith('uploads/marketplace/offers/', $offer->main_image_path);
        @unlink(public_path($offer->main_image_path));
    }

    /** @return MarketplaceOffer approuvée & en ligne, avec 2 variantes */
    private function liveOfferWithVariants(): MarketplaceOffer
    {
        $this->actingAs($this->vendor->user)->post(route('vendor.offers.store'), $this->offerPayload());
        $offer = MarketplaceOffer::first();
        app(\App\Services\Marketplace\OfferSubmissionService::class)->submit($offer->fresh(), $this->vendor->user);
        app(\App\Services\Marketplace\OfferModerationService::class)->approve($offer->fresh(), $this->admin());

        return $offer->fresh();
    }

    public function test_offer_page_renders_a_variant_picker(): void
    {
        $offer = $this->liveOfferWithVariants();

        $this->get(route('marketplace.shop.offer', $offer->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Marketplace/Offer')
                ->where('offer.variants.0.options.Couleur', 'Écru')
                ->where('offer.variants.0.options.Taille', 'M')
                ->has('offer.variants', 2));
    }

    public function test_cart_requires_a_variant_for_a_variant_offer(): void
    {
        $offer = $this->liveOfferWithVariants();

        $this->post(route('marketplace.cart.add'), ['offer_id' => $offer->id, 'quantity' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, app(MarketplaceCartService::class)->count());
    }

    public function test_checkout_uses_variant_price_and_reserves_variant_stock(): void
    {
        $offer = $this->liveOfferWithVariants();
        $indigo = $offer->variants()->where('sku', 'JUTE-IND-M')->first();

        $order = app(MarketplaceCheckoutService::class)->place(
            [['offer_id' => $offer->id, 'variant_id' => $indigo->id, 'qty' => 2]],
            ['email' => 'b@example.com'],
        );

        $item = $order->suborders()->first()->items()->first();
        $this->assertSame($indigo->id, $item->offer_variant_id);
        $this->assertSame('Couleur : Indigo · Taille : M', $item->variant_label);
        $this->assertEqualsWithDelta(27000, (float) $item->line_total, 0.01); // 13500 × 2
        $this->assertSame(2, $indigo->fresh()->stock_reserved);

        // Confirmation : le stock de la variante baisse, pas celui de l'offre.
        app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh());
        $this->assertSame(2, $indigo->fresh()->stock);           // 4 - 2
        $this->assertSame(0, $indigo->fresh()->stock_reserved);
    }

    public function test_checkout_rejects_overselling_a_variant(): void
    {
        $offer = $this->liveOfferWithVariants();
        $ecru = $offer->variants()->where('sku', 'JUTE-ECRU-M')->first();

        $this->expectException(\App\Exceptions\InsufficientStockException::class);
        app(MarketplaceCheckoutService::class)->place(
            [['offer_id' => $offer->id, 'variant_id' => $ecru->id, 'qty' => 99]],
            ['email' => 'b@example.com'],
        );
    }

    public function test_editing_removes_a_variant(): void
    {
        $offer = $this->liveOfferWithVariants();
        $keep = $offer->variants()->where('sku', 'JUTE-ECRU-M')->first();

        // Ré-envoie seulement la variante Écru.
        $this->actingAs($this->vendor->user)->put(route('vendor.offers.update', $offer), $this->offerPayload([
            'variants' => [
                ['id' => $keep->id, 'option_values' => ['Couleur' => 'Écru', 'Taille' => 'M'], 'stock' => 6, 'sku' => 'JUTE-ECRU-M', 'is_active' => 1],
            ],
        ]));

        // L'offre était en ligne : l'édition est mise en attente (révision),
        // la ligne cœur ne bouge pas — mais on peut vérifier la révision.
        $revision = $offer->fresh()->revisions()->where('state', 'pending')->latest('version')->first();
        $this->assertNotNull($revision);
        $this->assertCount(1, $revision->payload['variants']);
    }

    public function test_editing_a_draft_syncs_variants_in_place(): void
    {
        $this->actingAs($this->vendor->user)->post(route('vendor.offers.store'), $this->offerPayload());
        $offer = MarketplaceOffer::first();
        $keep = $offer->variants()->where('sku', 'JUTE-ECRU-M')->first();

        $this->actingAs($this->vendor->user)->put(route('vendor.offers.update', $offer), $this->offerPayload([
            'variants' => [
                ['id' => $keep->id, 'option_values' => ['Couleur' => 'Écru', 'Taille' => 'L'], 'stock' => 9, 'sku' => 'JUTE-ECRU-L', 'is_active' => 1],
            ],
        ]));

        $offer->refresh();
        $this->assertSame(1, $offer->variants()->count());
        $this->assertSame(9, $keep->fresh()->stock);
        $this->assertSame(['Couleur' => 'Écru', 'Taille' => 'L'], $keep->fresh()->option_values);
    }
}
