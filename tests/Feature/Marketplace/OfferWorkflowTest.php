<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Marketplace\OfferModerated;
use App\Notifications\Marketplace\OfferSubmitted;
use App\Services\Marketplace\OfferSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OfferWorkflowTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'category_id' => $this->category->id,
            'type' => 'physical',
            'title' => 'Coffret artisanal en bois de teck',
            'summary' => 'Fait main à Porto-Novo',
            'description' => str_repeat('Un très bel objet artisanal fabriqué localement. ', 4),
            'price' => 15000,
            'detail' => ['stock' => 8, 'delivery_type' => 'file'],
        ], $overrides);
    }

    private function actingAsVendor()
    {
        return $this->actingAs($this->vendor->user);
    }

    public function test_vendor_creates_a_draft_with_a_type_detail_row(): void
    {
        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload())->assertRedirect();

        $offer = MarketplaceOffer::first();
        $this->assertSame('draft', $offer->status);
        $this->assertSame('physical', $offer->type);
        $this->assertSame(8, $offer->physical->stock);
        $this->assertSame($this->vendor->id, $offer->vendor_profile_id);
    }

    public function test_category_that_forbids_the_type_is_rejected(): void
    {
        $digitalOnly = MarketplaceCategory::factory()->types(['digital'])->create();

        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload([
            'category_id' => $digitalOnly->id,
            'type' => 'physical',
        ]))->assertSessionHasErrors('type');
    }

    public function test_price_outside_category_bounds_is_rejected(): void
    {
        $this->category->update(['ruleset' => ['require_approval' => true, 'min_price' => 20000]]);

        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload(['price' => 15000]))
            ->assertSessionHasErrors('price');
    }

    public function test_required_custom_field_is_enforced_and_stored(): void
    {
        MarketplaceCategoryField::factory()->key('essence')->required()
            ->for($this->category, 'category')->create();

        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload())
            ->assertSessionHasErrors('custom_fields.essence');

        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload([
            'custom_fields' => ['essence' => 'Teck'],
        ]))->assertRedirect();

        $this->assertSame(['essence' => 'Teck'], MarketplaceOffer::first()->custom_fields);
    }

    public function test_submit_snapshots_a_revision_and_notifies_admins(): void
    {
        $admin = $this->admin();
        $offer = $this->makeDraft();

        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer))->assertRedirect();

        $offer->refresh();
        $this->assertSame('pending_review', $offer->status);
        $this->assertSame(1, $offer->version);
        $this->assertSame(1, $offer->revisions()->where('state', 'pending')->count());
        Notification::assertSentTo($admin, OfferSubmitted::class);
    }

    public function test_admin_approval_first_publishes_the_offer(): void
    {
        $admin = $this->admin();
        $offer = $this->submittedOffer();

        $this->actingAs($admin)->post(route('admin.marketplace.offers.approve', $offer))->assertRedirect();

        $offer->refresh();
        $this->assertSame('approved', $offer->status);
        $this->assertTrue($offer->is_online);
        $this->assertNotNull($offer->first_published_at);
        $this->assertSame('approved', $offer->revisions()->latest('version')->first()->state);
        $this->assertDatabaseHas('marketplace_offer_reviews', [
            'offer_id' => $offer->id, 'decision' => 'approved', 'reviewer_id' => $admin->id,
        ]);
        Notification::assertSentTo($offer->vendor->user, OfferModerated::class);
    }

    public function test_editing_a_live_offer_stages_a_revision_without_touching_the_public_row(): void
    {
        $admin = $this->admin();
        $offer = $this->submittedOffer();
        $this->actingAs($admin)->post(route('admin.marketplace.offers.approve', $offer));
        $offer->refresh();
        $originalTitle = $offer->title;

        // Le vendeur modifie l'offre en ligne.
        $this->actingAsVendor()->put(route('vendor.offers.update', $offer), $this->payload([
            'title' => 'Coffret artisanal — nouvelle édition',
        ]))->assertRedirect();

        $offer->refresh();
        $this->assertSame($originalTitle, $offer->title, 'la ligne publique ne doit pas bouger');
        $this->assertSame('approved', $offer->status);
        $this->assertTrue($offer->is_online);
        $this->assertSame(1, $offer->revisions()->where('state', 'pending')->count());

        // Re-soumission puis approbation : le contenu est appliqué.
        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer))->assertRedirect();
        $this->assertSame('pending_review', $offer->fresh()->status);
        $this->assertTrue($offer->fresh()->is_online, 'reste en ligne pendant la revue');

        $this->actingAs($admin)->post(route('admin.marketplace.offers.approve', $offer))->assertRedirect();
        $this->assertSame('Coffret artisanal — nouvelle édition', $offer->fresh()->title);
    }

    public function test_rejecting_a_never_published_offer_marks_it_rejected(): void
    {
        $offer = $this->submittedOffer();

        $this->actingAs($this->admin())->post(route('admin.marketplace.offers.reject', $offer), [
            'reason' => 'Photos de trop faible qualité.',
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame('rejected', $offer->status);
        $this->assertFalse($offer->is_online);
    }

    public function test_rejecting_a_live_offers_new_revision_keeps_the_published_version(): void
    {
        $admin = $this->admin();
        $offer = $this->submittedOffer();
        $this->actingAs($admin)->post(route('admin.marketplace.offers.approve', $offer));

        $this->actingAsVendor()->put(route('vendor.offers.update', $offer), $this->payload(['title' => 'Version douteuse']));
        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer));

        $this->actingAs($admin)->post(route('admin.marketplace.offers.reject', $offer), [
            'reason' => 'Le nouveau titre ne convient pas.',
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame('approved', $offer->status);
        $this->assertTrue($offer->is_online);
        $this->assertNotSame('Version douteuse', $offer->title);
    }

    public function test_request_changes_then_vendor_edits_and_resubmits(): void
    {
        $admin = $this->admin();
        $offer = $this->submittedOffer();

        $this->actingAs($admin)->post(route('admin.marketplace.offers.request-changes', $offer), [
            'note' => 'Merci de préciser les dimensions dans la description.',
        ])->assertRedirect();
        $this->assertSame('changes_requested', $offer->fresh()->status);

        $this->actingAsVendor()->put(route('vendor.offers.update', $offer), $this->payload([
            'description' => str_repeat('Description complétée avec les dimensions 30x20x15 cm. ', 3),
        ]))->assertRedirect();
        $this->assertSame('draft', $offer->fresh()->status);

        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer))->assertRedirect();
        $this->assertSame('pending_review', $offer->fresh()->status);
    }

    public function test_withdraw_returns_a_pending_offer_to_draft(): void
    {
        $offer = $this->submittedOffer();

        $this->actingAsVendor()->post(route('vendor.offers.withdraw', $offer))->assertRedirect();

        $this->assertSame('draft', $offer->fresh()->status);
        $this->assertSame(0, $offer->revisions()->where('state', 'pending')->count());
    }

    public function test_trusted_vendor_offers_are_auto_approved_when_enabled(): void
    {
        Setting::put('marketplace.auto_approve_trusted_vendors', '1');
        $this->vendor->update(['is_trusted' => true]);
        $offer = $this->makeDraft();

        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer))->assertRedirect();

        $this->assertSame('approved', $offer->fresh()->status);
        $this->assertTrue($offer->fresh()->is_online);
    }

    public function test_vendor_publish_toggle_only_works_on_approved_offers(): void
    {
        $admin = $this->admin();
        $offer = $this->submittedOffer();
        $this->actingAs($admin)->post(route('admin.marketplace.offers.approve', $offer));

        $this->actingAsVendor()->post(route('vendor.offers.unpublish', $offer))->assertRedirect();
        $this->assertFalse($offer->fresh()->is_online);

        $this->actingAsVendor()->post(route('vendor.offers.publish', $offer))->assertRedirect();
        $this->assertTrue($offer->fresh()->is_online);
    }

    public function test_offer_screens_render(): void
    {
        MarketplaceCategoryField::factory()->key('essence')->select([
            ['value' => 'teck', 'label' => 'Teck'], ['value' => 'ebene', 'label' => 'Ébène'],
        ])->for($this->category, 'category')->create();

        $admin = $this->admin();
        $offer = $this->submittedOffer();

        $this->actingAsVendor()->get(route('vendor.offers.index'))->assertOk();
        $this->actingAsVendor()->get(route('vendor.offers.create'))->assertOk()->assertSee('Nouvelle offre');
        $this->actingAsVendor()->get(route('vendor.offers.edit', $offer))->assertOk()->assertSee('Essence');

        $this->actingAs($admin)->get(route('admin.marketplace.offers.index'))->assertOk()->assertSee($offer->title);
        $this->actingAs($admin)->get(route('admin.marketplace.offers.show', $offer))->assertOk()->assertSee('Décision');
    }

    public function test_a_vendor_cannot_touch_another_vendors_offer(): void
    {
        $other = VendorProfile::factory()->active()->create();
        $offer = MarketplaceOffer::factory()->forVendor($other)->inCategory($this->category)->create();

        $this->actingAsVendor()->get(route('vendor.offers.edit', $offer))->assertForbidden();
        $this->actingAsVendor()->post(route('vendor.offers.submit', $offer))->assertForbidden();
    }

    /* ---------------- helpers ---------------- */

    private function makeDraft(): MarketplaceOffer
    {
        $this->actingAsVendor()->post(route('vendor.offers.store'), $this->payload());

        return MarketplaceOffer::latest()->first();
    }

    private function submittedOffer(): MarketplaceOffer
    {
        $offer = $this->makeDraft();
        app(OfferSubmissionService::class)->submit($offer->fresh(), $this->vendor->user);

        return $offer->fresh();
    }
}
