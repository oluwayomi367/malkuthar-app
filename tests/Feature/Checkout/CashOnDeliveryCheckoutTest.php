<?php

namespace Tests\Feature\Checkout;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paiement à la livraison au checkout (§22/§26) : la commande est
 * enregistrée sans qu'aucun PSP ne soit sollicité et reste « en attente »
 * jusqu'à l'encaissement par un administrateur.
 */
class CashOnDeliveryCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'cash_on_delivery',
            'payment_provider' => 'cod',
            'email' => 'buyer@example.com',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $overrides);
    }

    public function test_cod_checkout_creates_a_pending_order_without_a_payment_url(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $response = $this->post('/checkout', $this->payload($product))->assertCreated();

        $response->assertJsonPath('data.payment.status', 'cod');
        $this->assertNull($response->json('data.payment.url'));

        $order = Order::first();
        $this->assertSame('cod', $order->payment_provider);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->payment_reference);

        // Aucune commission tant que le paiement n'est pas encaissé.
        $this->assertSame(0, LedgerEntry::where('source_type', 'commission')->count());
    }

    public function test_cod_checkout_reserves_physical_stock(): void
    {
        $affiliate = User::factory()->affiliate()->create(['code_parrainage' => 'JEAN125']);
        $product = Product::factory()->physical()->create(['stock_available' => 5]);

        $this->post('/checkout', $this->payload($product, ['referral_code' => 'JEAN125', 'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '+22990000000',
            'country' => 'Bénin', 'city' => 'Cotonou', 'address' => 'Rue 1',
        ]]))->assertCreated();

        $product->refresh();
        $this->assertSame(4, (int) $product->stock_available);
        $this->assertSame(1, (int) $product->stock_reserved);

        $order = Order::first();
        $this->assertSame($affiliate->id, $order->referring_affiliate_id);
    }

    public function test_unknown_provider_is_still_rejected(): void
    {
        $product = Product::factory()->digitalAffiliable()->create();

        $this->postJson('/checkout', $this->payload($product, ['payment_provider' => 'bitcoin']))
            ->assertStatus(422);
    }
}
