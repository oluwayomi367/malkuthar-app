<?php

namespace Tests\Feature\Payments;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\FedaPayClient;
use App\Services\Payments\FedaPayGateway;
use App\Services\Payments\PaymentInitiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\Support\FakeFedaPayClient;
use Tests\TestCase;

class FedaPayFlowTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeFedaPayClient::reset();
        $this->app->bind(FedaPayClient::class, fn () => new FakeFedaPayClient);
        // On veut ici tester le vrai FedaPayGateway (pas le double
        // générique bindé par défaut dans TestCase::setUp()).
        $this->app->bind(FedaPayGateway::class, fn ($app) => new FedaPayGateway($app->make(FedaPayClient::class)));
    }

    private function pendingDigitalOrder(?User $referring, float $amount = 10000): Order
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => $amount]);

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'referring_affiliate_id' => $referring?->id,
            'order_number' => 'TEST-'.Str::upper(Str::random(10)),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_provider' => 'fedapay',
            'currency' => 'XOF',
            'subtotal' => $amount,
            'total_amount' => $amount,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_type' => 'digital',
            'is_affiliable_snapshot' => true,
            'quantity' => 1,
            'unit_price' => $amount,
            'discount_amount' => 0,
            'total_price' => $amount,
            'currency' => 'XOF',
            'commission_base_amount' => $amount,
        ]);

        return $order->fresh('items');
    }

    public function test_initiation_returns_a_payment_url_and_stores_the_reference(): void
    {
        $order = $this->pendingDigitalOrder(null);

        $session = app(PaymentInitiationService::class)->initiate($order, ['email' => 'buyer@example.com']);

        $this->assertSame('fedapay', $session->provider);
        $this->assertNotEmpty($session->paymentUrl);
        $this->assertSame($order->fresh()->payment_reference, $session->reference);
    }

    public function test_webhook_confirms_payment_and_distributes_commissions(): void
    {
        $jean = $this->affiliate();
        $order = $this->pendingDigitalOrder($jean);
        $order->update(['payment_reference' => '9001']);

        FakeFedaPayClient::seed(9001, [
            'status' => 'approved',
            'amount' => 10000,
            'currency' => (object) ['iso' => 'XOF'],
            'metadata' => (object) ['order_id' => $order->id],
        ]);

        $this->postWebhook(['name' => 'transaction.approved', 'entity' => ['id' => 9001]])
            ->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEqualsWithDelta(1500, (float) $this->commissionAmount($jean), 0.001);
    }

    public function test_duplicate_webhook_does_not_duplicate_commissions(): void
    {
        $jean = $this->affiliate();
        $order = $this->pendingDigitalOrder($jean);
        $order->update(['payment_reference' => '9002']);

        FakeFedaPayClient::seed(9002, [
            'status' => 'approved',
            'metadata' => (object) ['order_id' => $order->id],
        ]);

        $payload = ['name' => 'transaction.approved', 'entity' => ['id' => 9002]];
        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk(); // §27

        $this->assertSame(1, LedgerEntry::where('order_id', $order->id)->where('source_type', 'commission')->count());
    }

    public function test_refund_webhook_reverses_commissions(): void
    {
        $jean = $this->affiliate();
        $order = $this->pendingDigitalOrder($jean);
        $order->update(['payment_reference' => '9003']);

        FakeFedaPayClient::seed(9003, ['status' => 'approved', 'metadata' => (object) ['order_id' => $order->id]]);
        $this->postWebhook(['name' => 'transaction.approved', 'entity' => ['id' => 9003]])->assertOk();

        FakeFedaPayClient::seed(9003, ['status' => 'refunded', 'metadata' => (object) ['order_id' => $order->id]]);
        $this->postWebhook(['name' => 'transaction.refunded', 'entity' => ['id' => 9003]])->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);

        // La commission n'avait pas encore été validée (délai §42) : elle
        // est simplement annulée, pas de reprise (cas couvert avec
        // reprise d'une commission déjà disponible dans CommissionRefundTest).
        $this->assertSame('cancelled', LedgerEntry::where('order_id', $order->id)
            ->where('source_type', 'commission')->value('status'));
    }

    public function test_webhook_without_signature_header_is_rejected(): void
    {
        $this->call('POST', route('webhooks.psp', ['provider' => 'fedapay']), [], [], [], [], json_encode([
            'name' => 'transaction.approved',
            'entity' => ['id' => 1],
        ]))->assertStatus(400);
    }

    private function commissionAmount(User $affiliate): ?string
    {
        return LedgerEntry::where('user_id', $affiliate->id)
            ->where('source_type', 'commission')
            ->where('level', 1)
            ->value('amount');
    }

    private function postWebhook(array $event)
    {
        return $this->call(
            'POST',
            route('webhooks.psp', ['provider' => 'fedapay']),
            [], [], [],
            ['HTTP_X_FEDAPAY_SIGNATURE' => 'test-signature'],
            json_encode($event)
        );
    }
}
