<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentVerification;
use App\Services\Payments\FedaPayClient;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * §22, §26 — mapping FedaPay : création transaction + token, lecture du
 * statut réel via l'API, et extraction de la référence depuis un webhook
 * signé. Le SDK réseau est remplacé par un mock de FedaPayClient.
 */
class FedaPayGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        return Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => 'CMD-'.Str::upper(Str::random(6)),
            'status' => 'pending',
            'payment_status' => 'pending',
            'currency' => 'XOF',
            'subtotal' => 10000,
            'total_amount' => 10000,
        ]);
    }

    private function gateway(FedaPayClient $client): FedaPayGateway
    {
        // `TestCase::setUp` bind un FakePaymentGateway sur FedaPayGateway :
        // on instancie donc la vraie classe directement avec notre mock.
        return new FedaPayGateway($client);
    }

    public function test_create_checkout_session_creates_transaction_then_token(): void
    {
        $order = $this->order();

        $client = Mockery::mock(FedaPayClient::class);
        $client->shouldReceive('createTransaction')
            ->once()
            ->with(Mockery::on(function (array $data) use ($order) {
                return $data['amount'] === 10000
                    && $data['currency'] === ['iso' => 'XOF']
                    && $data['custom_metadata'] === ['order_id' => $order->id]
                    && str_contains($data['callback_url'], $order->id);
            }))
            ->andReturn((object) ['id' => 4242]);
        $client->shouldReceive('generateToken')
            ->once()
            ->andReturn((object) ['token' => 'tok_abc', 'url' => 'https://sandbox-checkout.fedapay.com/pay/tok_abc']);

        $session = $this->gateway($client)->createCheckoutSession($order, [
            'first_name' => 'Awa', 'last_name' => 'D.', 'email' => 'awa@example.com',
        ]);

        $this->assertSame('fedapay', $session->provider);
        $this->assertSame('4242', $session->reference);
        $this->assertSame('https://sandbox-checkout.fedapay.com/pay/tok_abc', $session->paymentUrl);
    }

    public function test_verify_transaction_maps_statuses_and_reads_custom_metadata(): void
    {
        $client = Mockery::mock(FedaPayClient::class);
        $client->shouldReceive('retrieveTransaction')->with('4242')->andReturn((object) [
            'id' => 4242,
            'status' => 'approved',
            'amount' => 10000,
            'currency' => ['iso' => 'XOF'],
            'custom_metadata' => ['order_id' => 'order-uuid-1'],
        ]);

        $v = $this->gateway($client)->verifyTransaction('4242');

        $this->assertTrue($v->isPaid());
        $this->assertSame('XOF', $v->currency);
        $this->assertSame(10000.0, $v->amount);
        $this->assertSame('order-uuid-1', $v->metadata['order_id']);
    }

    public function test_verify_transaction_maps_declined_and_pending(): void
    {
        $declined = Mockery::mock(FedaPayClient::class);
        $declined->shouldReceive('retrieveTransaction')->andReturn((object) ['id' => 1, 'status' => 'declined']);
        $this->assertSame(PaymentVerification::FAILED, $this->gateway($declined)->verifyTransaction('1')->status);

        $pending = Mockery::mock(FedaPayClient::class);
        $pending->shouldReceive('retrieveTransaction')->andReturn((object) ['id' => 2, 'status' => 'pending']);
        $this->assertSame(PaymentVerification::PENDING, $this->gateway($pending)->verifyTransaction('2')->status);

        $refunded = Mockery::mock(FedaPayClient::class);
        $refunded->shouldReceive('retrieveTransaction')->andReturn((object) ['id' => 3, 'status' => 'refunded']);
        $this->assertTrue($this->gateway($refunded)->verifyTransaction('3')->isRefunded());
    }

    public function test_parse_webhook_returns_entity_id_from_a_signed_event(): void
    {
        $client = Mockery::mock(FedaPayClient::class);
        $client->shouldReceive('constructWebhookEvent')
            ->once()
            ->with('{"raw":true}', 't=1,s=abc', Mockery::any())
            ->andReturn((object) ['name' => 'transaction.approved', 'entity' => (object) ['id' => 4242]]);

        $ref = $this->gateway($client)->parseWebhook('{"raw":true}', [
            'x-fedapay-signature' => ['t=1,s=abc'],
        ]);

        $this->assertSame('4242', $ref);
    }

    public function test_parse_webhook_rejects_a_missing_or_bad_signature(): void
    {
        $client = Mockery::mock(FedaPayClient::class);

        // En-tête absent → null sans appeler le SDK.
        $this->assertNull($this->gateway($client)->parseWebhook('{}', []));

        // Signature invalide → le SDK lève, on renvoie null.
        $client->shouldReceive('constructWebhookEvent')->andThrow(new \RuntimeException('bad sig'));
        $this->assertNull($this->gateway($client)->parseWebhook('{}', ['x-fedapay-signature' => ['bogus']]));
    }
}
