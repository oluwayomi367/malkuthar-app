<?php

namespace Tests\Feature\Commerce;

use App\Models\Coupon;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\PaymentVerification;
use App\Services\AffiliationService;
use App\Services\CheckoutService;
use App\Services\OrderRefundService;
use App\Services\Payments\PaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

/**
 * §79 — coupons de réduction, avec impact explicite sur la BASE DE
 * CALCUL de la commission d'un produit digital affiliable.
 */
class CouponTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function checkout(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(Product $product, ?string $couponCode, ?User $referring = null, array $overrides = []): Order
    {
        return $this->checkout()->createOrderFromCheckout(
            data: array_merge([
                'payment_method' => 'mobile_money',
                'payment_provider' => 'fedapay',
                'coupon_code' => $couponCode,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ], $overrides),
            user: User::factory()->create(),
            productsById: collect([$product->id => $product]),
            referringAffiliate: $referring,
        );
    }

    public function test_percent_coupon_reduces_total_and_commission_base(): void
    {
        Coupon::create(['code' => 'PROMO20', 'type' => 'percent', 'value' => 20, 'is_active' => true]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $jean = $this->affiliate();

        $order = $this->order($product, 'PROMO20', $jean);
        $order->update(['payment_status' => 'paid']);
        app(AffiliationService::class)->distributeCommissions($order->fresh('items'));

        $this->assertEqualsWithDelta(2000, (float) $order->discount_total, 0.001);
        $this->assertEqualsWithDelta(8000, (float) $order->total_amount, 0.001);
        $this->assertEqualsWithDelta(8000, (float) $order->items->first()->commission_base_amount, 0.001);

        // §79 - commission calculée sur 8 000, pas sur 10 000.
        $this->assertEqualsWithDelta(1200, (float) LedgerEntry::where('level', 1)->value('amount'), 0.001);
    }

    public function test_fixed_coupon_applies_a_flat_discount(): void
    {
        Coupon::create(['code' => 'MINUS3K', 'type' => 'fixed', 'value' => 3000, 'is_active' => true]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $order = $this->order($product, 'MINUS3K');

        $this->assertEqualsWithDelta(7000, (float) $order->total_amount, 0.001);
        $this->assertEqualsWithDelta(7000, (float) $order->items->first()->commission_base_amount, 0.001);
    }

    public function test_expired_coupon_is_ignored(): void
    {
        Coupon::create([
            'code' => 'OLD', 'type' => 'percent', 'value' => 50, 'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $order = $this->order($product, 'OLD');

        $this->assertEqualsWithDelta(0, (float) $order->discount_total, 0.001);
        $this->assertEqualsWithDelta(10000, (float) $order->total_amount, 0.001);
        $this->assertNull($order->coupon_code);
    }

    public function test_coupon_targeting_other_products_does_not_apply(): void
    {
        $target = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $other = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        Coupon::create([
            'code' => 'ONLYOTHER', 'type' => 'percent', 'value' => 30, 'is_active' => true,
            'product_ids' => [$other->id],
        ]);

        $order = $this->order($target, 'ONLYOTHER');

        $this->assertEqualsWithDelta(0, (float) $order->discount_total, 0.001);
    }

    public function test_max_uses_is_enforced(): void
    {
        Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 1000, 'is_active' => true, 'max_uses' => 1]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $first = $this->order($product, 'ONCE');
        $second = $this->order($product, 'ONCE');

        $this->assertEqualsWithDelta(1000, (float) $first->discount_total, 0.001);
        $this->assertEqualsWithDelta(0, (float) $second->discount_total, 0.001);
        $this->assertSame(1, Coupon::where('code', 'ONCE')->value('used_count'));
    }

    public function test_refund_releases_the_coupon_use(): void
    {
        Coupon::create(['code' => 'REFUNDME', 'type' => 'fixed', 'value' => 1000, 'is_active' => true, 'max_uses' => 1]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $order = $this->order($product, 'REFUNDME');
        $this->assertSame(1, Coupon::where('code', 'REFUNDME')->value('used_count'));

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'r1')
        );
        app(OrderRefundService::class)->refund($order->fresh());

        $this->assertSame(0, Coupon::where('code', 'REFUNDME')->value('used_count'));
    }

    public function test_min_order_amount_not_met_drops_the_coupon(): void
    {
        Coupon::create([
            'code' => 'BIGONLY', 'type' => 'percent', 'value' => 10, 'is_active' => true,
            'min_order_amount' => 50000,
        ]);
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $order = $this->order($product, 'BIGONLY');

        $this->assertEqualsWithDelta(0, (float) $order->discount_total, 0.001);
        $this->assertNull($order->fresh()->coupon_code);
        $this->assertSame(0, Coupon::where('code', 'BIGONLY')->value('used_count'));
    }

    public function test_admin_can_create_a_coupon_and_others_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create();

        $payload = ['code' => 'NEW10', 'type' => 'percent', 'value' => 10];

        $this->actingAs($client)->post('/admin/coupons', $payload)->assertForbidden();
        $this->actingAs($admin)->from('/admin/coupons')->post('/admin/coupons', $payload)
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', ['code' => 'NEW10', 'type' => 'percent']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'coupon.created']);
    }
}
