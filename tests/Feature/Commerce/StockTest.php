<?php

namespace Tests\Feature\Commerce;

use App\Models\Product;
use App\Models\User;
use App\Payments\PaymentVerification;
use App\Services\CheckoutService;
use App\Services\OrderRefundService;
use App\Services\Payments\PaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10-§12 — gestion du stock des produits physiques : réservation au
 * checkout, vente à la confirmation du paiement, remise en stock au
 * remboursement (§80). Aucune commission générée (§4), déjà couvert par
 * CommissionChainTest::test_c_and_d_physical_and_service_generate_nothing.
 */
class StockTest extends TestCase
{
    use RefreshDatabase;

    private function checkout(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    public function test_checkout_reserves_stock_for_physical_items(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10, 'price' => 5000]);
        $user = User::factory()->create();

        $order = $this->checkout()->createOrderFromCheckout(
            data: ['payment_method' => 'mobile_money', 'payment_provider' => 'fedapay', 'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ]],
            user: $user,
            productsById: collect([$product->id => $product]),
        );

        $product->refresh();
        $this->assertSame(7, $product->stock_available);
        $this->assertSame(3, $product->stock_reserved);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'type' => 'reserved',
            'quantity' => 3,
        ]);
    }

    public function test_checkout_rejects_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 2, 'price' => 5000]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/checkout', [
            'payment_method' => 'mobile_money',
            'payment_provider' => 'fedapay',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'shipping_address' => [
                'first_name' => 'Jean', 'last_name' => 'Dupont', 'phone' => '+22990000000',
                'country' => 'Bénin', 'city' => 'Cotonou', 'address' => 'Rue 123',
            ],
        ])->assertStatus(409);

        $this->assertSame(2, $product->fresh()->stock_available);
    }

    public function test_payment_confirmation_converts_reservation_into_a_sale(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10, 'price' => 5000]);
        $user = User::factory()->create();

        $order = $this->checkout()->createOrderFromCheckout(
            data: ['payment_method' => 'mobile_money', 'payment_provider' => 'fedapay', 'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ]],
            user: $user,
            productsById: collect([$product->id => $product]),
        );

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-1')
        );

        $product->refresh();
        $this->assertSame(8, $product->stock_available); // inchangé depuis la réservation
        $this->assertSame(0, $product->stock_reserved);  // réservation convertie en vente
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sold', 'quantity' => 2]);
    }

    public function test_refund_restocks_physical_items(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10, 'price' => 5000]);
        $user = User::factory()->create();

        $order = $this->checkout()->createOrderFromCheckout(
            data: ['payment_method' => 'mobile_money', 'payment_provider' => 'fedapay', 'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ]],
            user: $user,
            productsById: collect([$product->id => $product]),
        );

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-2')
        );

        app(OrderRefundService::class)->refund($order->fresh());

        $product->refresh();
        $this->assertSame(10, $product->stock_available); // entièrement restocké
        $this->assertSame(0, $product->stock_sold);       // la vente est décomptée
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'returned', 'quantity' => 2]);
    }

    public function test_confirmed_sale_increments_sold_counter(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10, 'price' => 5000]);
        $user = User::factory()->create();

        $order = $this->checkout()->createOrderFromCheckout(
            data: ['payment_method' => 'mobile_money', 'payment_provider' => 'fedapay', 'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ]],
            user: $user,
            productsById: collect([$product->id => $product]),
        );

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-sold')
        );

        $this->assertSame(3, $product->fresh()->stock_sold);
    }

    /**
     * §12 — le stock est celui de la VARIANTE choisie, pas du produit.
     */
    public function test_checkout_reserves_and_sells_variant_stock(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 999, 'price' => 5000]);
        $variant = $product->variants()->create([
            'name' => 'Terre / M', 'options' => ['couleur' => 'Terre', 'taille' => 'M'],
            'sku' => 'TER-M', 'price' => 8000, 'stock_available' => 4,
        ]);
        $user = User::factory()->create();

        $order = $this->checkout()->createOrderFromCheckout(
            data: ['payment_method' => 'mobile_money', 'payment_provider' => 'fedapay', 'items' => [
                ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2],
            ]],
            user: $user,
            productsById: collect([$product->id => $product]),
        );

        // Prix de la variante appliqué, stock de la variante réservé.
        $item = $order->items()->first();
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame('8000.00', $item->unit_price);

        $variant->refresh();
        $this->assertSame(2, $variant->stock_available);
        $this->assertSame(2, $variant->stock_reserved);
        $this->assertSame(999, $product->fresh()->stock_available); // intact
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id, 'type' => 'reserved', 'quantity' => 2,
        ]);

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-var')
        );

        $variant->refresh();
        $this->assertSame(0, $variant->stock_reserved);
        $this->assertSame(2, $variant->stock_sold);

        app(OrderRefundService::class)->refund($order->fresh());

        $variant->refresh();
        $this->assertSame(4, $variant->stock_available); // remis en stock
        $this->assertSame(0, $variant->stock_sold);
    }

    public function test_checkout_rejects_when_variant_stock_is_insufficient(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 999, 'price' => 5000]);
        $variant = $product->variants()->create([
            'name' => 'Sable / L', 'options' => ['taille' => 'L'], 'sku' => 'SAB-L', 'stock_available' => 1,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/checkout', [
            'payment_method' => 'mobile_money',
            'payment_provider' => 'fedapay',
            'items' => [['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 3]],
            'shipping_address' => [
                'first_name' => 'Jean', 'last_name' => 'Dupont', 'phone' => '+22990000000',
                'country' => 'Bénin', 'city' => 'Cotonou', 'address' => 'Rue 123',
            ],
        ])->assertStatus(409);

        $this->assertSame(1, $variant->fresh()->stock_available);
    }
}
