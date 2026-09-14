<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\Marketplace\VendorLedgerEntry;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Services\Marketplace\MarketplaceCheckoutService;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FulfilmentReviewTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
        \App\Models\Setting::put('marketplace.default_commission_rate', 0.10);
        Notification::fake();
    }

    /** @return array{0: \App\Models\Marketplace\MarketplaceOrder, 1: MarketplaceSuborder, 2: VendorProfile} */
    private function paidOrder(User $buyer, int $stock = 5): array
    {
        $vendor = VendorProfile::factory()->active()->create();
        $offer = MarketplaceOffer::factory()->approved()->withStock($stock)->for($vendor, 'vendor')->create(['price' => 10000]);
        $order = app(MarketplaceCheckoutService::class)->place([$offer->id => 2], ['email' => $buyer->email], $buyer);
        app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh());

        return [$order->fresh(), $order->fresh()->suborders()->first(), $vendor];
    }

    public function test_vendor_ships_and_completes_a_suborder(): void
    {
        [$order, $sub, $vendor] = $this->paidOrder(User::factory()->create());

        $this->actingAs($vendor->user)->post(route('vendor.orders.ship', $sub), [
            'carrier' => 'DHL', 'tracking' => 'XZ123',
        ])->assertRedirect();

        $sub->refresh();
        $this->assertSame('shipped', $sub->fulfilment_status);
        $this->assertSame('XZ123', $sub->tracking_number);

        $this->actingAs($vendor->user)->post(route('vendor.orders.advance', $sub), ['to' => 'completed'])->assertRedirect();
        $this->assertSame('completed', $sub->fresh()->fulfilment_status);
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_vendor_cannot_fulfil_another_vendors_suborder(): void
    {
        [, $sub] = $this->paidOrder(User::factory()->create());
        $intruder = VendorProfile::factory()->active()->create();

        $this->actingAs($intruder->user)->post(route('vendor.orders.ship', $sub))->assertForbidden();
    }

    public function test_buyer_reviews_a_purchased_item_and_rating_is_recomputed(): void
    {
        $buyer = User::factory()->create();
        [, $sub] = $this->paidOrder($buyer);
        $item = $sub->items()->first();

        $this->actingAs($buyer)->post(route('marketplace.reviews.store'), [
            'order_item_id' => $item->id, 'rating' => 4, 'title' => 'Très bien', 'body' => 'Conforme.',
        ])->assertRedirect();

        $this->assertDatabaseHas('marketplace_reviews', ['marketplace_order_item_id' => $item->id, 'rating' => 4]);
        $this->assertSame(1, $item->offer->fresh()->rating_count);
        $this->assertSame('4.00', (string) $item->offer->fresh()->rating_avg);
    }

    public function test_review_requires_a_matching_paid_purchase_and_is_unique(): void
    {
        $buyer = User::factory()->create();
        [, $sub] = $this->paidOrder($buyer);
        $item = $sub->items()->first();

        // Un autre utilisateur ne peut pas noter.
        $this->actingAs(User::factory()->create())->post(route('marketplace.reviews.store'), [
            'order_item_id' => $item->id, 'rating' => 5,
        ])->assertSessionHasErrors('review');

        // Doublon interdit.
        $this->actingAs($buyer)->post(route('marketplace.reviews.store'), ['order_item_id' => $item->id, 'rating' => 5]);
        $this->actingAs($buyer)->post(route('marketplace.reviews.store'), ['order_item_id' => $item->id, 'rating' => 3])
            ->assertSessionHasErrors('review');
        $this->assertSame(1, MarketplaceReview::count());
    }

    public function test_vendor_replies_and_admin_hides_a_review(): void
    {
        $buyer = User::factory()->create();
        [, $sub, $vendor] = $this->paidOrder($buyer);
        $item = $sub->items()->first();
        $this->actingAs($buyer)->post(route('marketplace.reviews.store'), ['order_item_id' => $item->id, 'rating' => 2]);
        $review = MarketplaceReview::first();

        $this->actingAs($vendor->user)->post(route('vendor.reviews.reply', $review), ['reply' => 'Merci, on s\'améliore.'])
            ->assertRedirect();
        $this->assertNotNull($review->fresh()->vendor_replied_at);

        $this->actingAs($this->admin())->post(route('admin.marketplace.reviews.moderate', $review), ['status' => 'hidden'])
            ->assertRedirect();
        $this->assertSame('hidden', $review->fresh()->status);
        $this->assertSame(0, $item->offer->fresh()->rating_count);
    }

    public function test_admin_refund_reverses_settlement_and_restocks(): void
    {
        [$order, $sub, $vendor] = $this->paidOrder(User::factory()->create(), stock: 5);
        // stock: 5 - 2 vendus = 3
        $this->assertSame(3, $sub->items->first()->offer->physical->fresh()->stock);

        // On libère d'abord le crédit pour tester le clawback.
        VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->update(['available_at' => now()->subDay()]);
        app(VendorSettlementService::class)->releaseDue();

        $this->actingAs($this->admin())->post(route('admin.marketplace.suborders.refund', $sub), [
            'reason' => 'Article défectueux',
        ])->assertRedirect();

        $sub->refresh();
        $this->assertSame('reversed', $sub->settlement_status);
        $this->assertSame('cancelled', $sub->fulfilment_status);
        $this->assertSame(5, $sub->items->first()->offer->physical->fresh()->stock); // restocké
        $this->assertSame('refunded', $order->fresh()->status);

        $balance = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(0, (float) $balance->available_amount, 0.01);
    }

    public function test_service_booking_confirmation(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $offer = MarketplaceOffer::factory()->approved()->type('service')->for($vendor, 'vendor')->create();
        $buyer = User::factory()->create();
        $order = app(MarketplaceCheckoutService::class)->place([$offer->id => 1], ['email' => $buyer->email], $buyer);
        app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh());
        $sub = $order->fresh()->suborders()->first();
        $item = $sub->items()->first();

        $this->actingAs($vendor->user)->post(route('vendor.orders.confirm-booking', [$sub, $item]))->assertRedirect();
        $this->assertSame('processing', $item->fresh()->fulfilment_status);
    }
}
