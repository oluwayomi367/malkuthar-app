<?php

namespace Tests\Feature\Marketplace;

use App\Models\LedgerEntry;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOrder;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Notifications\Marketplace\MarketplaceVendorSale;
use App\Services\Marketplace\MarketplaceCheckoutService;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
        \App\Models\Setting::put('marketplace.default_commission_rate', 0.15);
        \App\Models\Setting::put('marketplace.settlement_hold_days', 14);
    }

    private function liveOffer(array $attrs = [], ?VendorProfile $vendor = null): MarketplaceOffer
    {
        return MarketplaceOffer::factory()->approved()
            ->for($vendor ?? VendorProfile::factory()->active(), 'vendor')
            ->create($attrs);
    }

    public function test_checkout_splits_a_multi_vendor_cart_and_freezes_commission(): void
    {
        $category = MarketplaceCategory::factory()->create();
        $a = $this->liveOffer(['price' => 10000, 'category_id' => $category->id]);
        $b = $this->liveOffer(['price' => 5000, 'category_id' => $category->id]);   // autre vendeur

        $order = app(MarketplaceCheckoutService::class)->place(
            [$a->id => 2, $b->id => 1],
            ['email' => 'client@example.com', 'provider' => 'cod'],
        );

        $this->assertSame('2', (string) $order->suborders()->count());
        $this->assertEqualsWithDelta(25000, (float) $order->grand_total, 0.01);

        $subA = $order->suborders()->where('vendor_profile_id', $a->vendor_profile_id)->first();
        $this->assertEqualsWithDelta(20000, (float) $subA->vendor_gross, 0.01);
        $this->assertSame('0.1500', (string) $subA->commission_rate);
        $this->assertEqualsWithDelta(3000, (float) $subA->commission_amount, 0.01);
        $this->assertEqualsWithDelta(17000, (float) $subA->vendor_net, 0.01);
    }

    public function test_checkout_reserves_stock_and_rejects_overselling(): void
    {
        $offer = $this->liveOffer()->fresh();
        $offer->physical()->create(['offer_id' => $offer->id, 'stock' => 3, 'stock_reserved' => 0]);

        app(MarketplaceCheckoutService::class)->place(
            [$offer->id => 2], ['email' => 'c@example.com'],
        );
        $this->assertSame(2, $offer->physical->fresh()->stock_reserved);

        $this->expectException(\App\Exceptions\InsufficientStockException::class);
        app(MarketplaceCheckoutService::class)->place(
            [$offer->id => 2], ['email' => 'c@example.com'],
        );
    }

    public function test_checkout_refuses_a_non_visible_offer(): void
    {
        $draft = MarketplaceOffer::factory()->create();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(MarketplaceCheckoutService::class)->place([$draft->id => 1], ['email' => 'c@example.com']);
    }

    public function test_confirm_paid_opens_settlement_window_decrements_stock_and_skips_affiliation(): void
    {
        Notification::fake();
        $offer = $this->liveOffer(['price' => 8000])->fresh();
        $offer->physical()->create(['offer_id' => $offer->id, 'stock' => 5, 'stock_reserved' => 0]);

        $order = app(MarketplaceCheckoutService::class)->place([$offer->id => 2], ['email' => 'c@example.com']);

        $paid = app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh());
        $this->assertTrue($paid);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);

        $sub = $order->suborders()->first();
        $this->assertSame('pending', $sub->settlement_status);
        $this->assertEqualsWithDelta(
            $order->paid_at->copy()->addDays(14)->timestamp,
            $sub->available_at->timestamp,
            5,
        );

        $offer->physical->refresh();
        $this->assertSame(3, $offer->physical->stock);          // 5 - 2
        $this->assertSame(0, $offer->physical->stock_reserved);  // réservation consommée
        $this->assertSame(2, $offer->fresh()->sales_count);

        // Aucune commission d'affiliation ne doit avoir été créée.
        $this->assertSame(0, LedgerEntry::query()->where('source_type', 'commission')->count());

        Notification::assertSentTo($sub->vendor->user, MarketplaceVendorSale::class);
    }

    public function test_confirm_paid_is_idempotent(): void
    {
        Notification::fake();
        $order = app(MarketplaceCheckoutService::class)->place(
            [$this->liveOffer(['price' => 3000])->id => 1], ['email' => 'c@example.com'],
        );

        $svc = app(MarketplacePaymentConfirmationService::class);
        $this->assertTrue($svc->confirmPaid($order->fresh()));
        $this->assertFalse($svc->confirmPaid($order->fresh()));
        $this->assertSame(1, $order->fresh()->suborders()->first()->items->sum('quantity') > 0 ? 1 : 0);
    }

    public function test_cancel_unpaid_order_releases_reserved_stock(): void
    {
        $offer = $this->liveOffer()->fresh();
        $offer->physical()->create(['offer_id' => $offer->id, 'stock' => 4, 'stock_reserved' => 0]);
        $order = app(MarketplaceCheckoutService::class)->place([$offer->id => 3], ['email' => 'c@example.com']);
        $this->assertSame(3, $offer->physical->fresh()->stock_reserved);

        app(MarketplacePaymentConfirmationService::class)->cancelUnpaid($order->fresh(), 'Test');

        $this->assertSame(0, $offer->physical->fresh()->stock_reserved);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->suborders()->first()->settlement_status);
    }

    public function test_full_http_checkout_flow_via_cod(): void
    {
        $offer = $this->liveOffer(['title' => 'Panier HTTP', 'price' => 12000]);

        $this->post(route('marketplace.cart.add'), ['offer_id' => $offer->id, 'quantity' => 2])
            ->assertRedirect(route('marketplace.cart'));

        $this->get(route('marketplace.checkout'))->assertOk()->assertSee('Panier HTTP');

        $res = $this->post(route('marketplace.checkout.store'), [
            'email' => 'acheteur@example.com',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = MarketplaceOrder::firstWhere('buyer_email', 'acheteur@example.com');
        $this->assertNotNull($order);
        $this->assertEqualsWithDelta(24000, (float) $order->grand_total, 0.01);
        $res->assertRedirect(route('marketplace.checkout.confirmation', $order->order_number));

        $this->get(route('marketplace.checkout.confirmation', $order->order_number))
            ->assertOk()->assertSee($order->order_number);
    }

    public function test_admin_can_mark_a_marketplace_order_paid(): void
    {
        Notification::fake();
        $order = app(MarketplaceCheckoutService::class)->place(
            [$this->liveOffer(['price' => 6000])->id => 1], ['email' => 'c@example.com'],
        );

        $this->actingAs($this->admin())
            ->post(route('admin.marketplace.orders.mark-paid', $order))
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'marketplace.order.marked_paid']);
    }

    public function test_webhook_routes_MP_references_to_the_marketplace_confirmation(): void
    {
        $order = app(MarketplaceCheckoutService::class)->place(
            [$this->liveOffer(['price' => 9000])->id => 1], ['email' => 'c@example.com'],
        );

        $verification = new \App\Payments\PaymentVerification(
            status: \App\Payments\PaymentVerification::PAID,
            reference: $order->payment_reference,
        );

        // Simule l'aiguillage effectué par ProcessPaymentWebhook.
        $this->assertTrue(str_starts_with($order->payment_reference, 'MP-'));
        app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh(), $verification->reference);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
