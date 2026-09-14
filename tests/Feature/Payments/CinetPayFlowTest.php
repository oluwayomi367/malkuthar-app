<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\AffiliationService;
use App\Services\Payments\CinetPayGateway;
use App\Services\Payments\PaymentInitiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

class CinetPayFlowTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // On veut ici tester le vrai CinetPayGateway (HTTP simulé via Http::fake).
        $this->app->bind(CinetPayGateway::class, fn () => new CinetPayGateway);

        config([
            'services.cinetpay.api_key' => 'test-key',
            'services.cinetpay.site_id' => 'test-site',
            'services.cinetpay.secret_key' => '', // vérif token désactivée, on s'appuie sur le re-check API (§26)
        ]);
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
            'payment_provider' => 'cinetpay',
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

    public function test_initiation_returns_a_payment_url(): void
    {
        Http::fake([
            '*/payment' => Http::response([
                'code' => '201',
                'data' => ['payment_url' => 'https://checkout.cinetpay.com/abc', 'payment_token' => 'tok_abc'],
            ]),
        ]);

        $order = $this->pendingDigitalOrder(null);

        $session = app(PaymentInitiationService::class)->initiate($order, ['email' => 'buyer@example.com']);

        $this->assertSame('cinetpay', $session->provider);
        $this->assertSame('https://checkout.cinetpay.com/abc', $session->paymentUrl);
        $this->assertSame($order->order_number, $order->fresh()->payment_reference);
    }

    public function test_webhook_confirms_payment_via_api_check(): void
    {
        $jean = $this->affiliate();
        $order = $this->pendingDigitalOrder($jean);
        $order->update(['payment_reference' => $order->order_number]);

        Http::fake([
            '*/payment/check' => Http::response([
                'data' => ['status' => 'ACCEPTED', 'amount' => 10000, 'currency' => 'XOF'],
            ]),
        ]);

        $this->postCinetPayWebhook(['cpm_trans_id' => $order->order_number])->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'source_type' => 'commission',
            'level' => 1,
            'amount' => 1500,
        ]);
    }

    public function test_webhook_without_transaction_id_is_rejected(): void
    {
        $this->postCinetPayWebhook(['not_a_trans_id' => 'x'])->assertStatus(400);
    }

    public function test_refund_webhook_reverses_an_already_available_commission(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->pendingDigitalOrder($jean);
        $order->update(['payment_reference' => $order->order_number]);

        // Deux appels successifs à /payment/check doivent renvoyer des
        // statuts différents : une séquence (et non deux Http::fake()
        // successifs sur le même pattern, dont seul le premier gagnerait).
        Http::fakeSequence('*/payment/check')
            ->push(['data' => ['status' => 'ACCEPTED']])
            ->push(['data' => ['status' => 'REFUNDED']]);

        $this->postCinetPayWebhook(['cpm_trans_id' => $order->order_number])->assertOk();
        app(AffiliationService::class)->releaseDueCommissions(); // -> available

        $this->postCinetPayWebhook(['cpm_trans_id' => $order->order_number])->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'source_type' => 'commission_reversal',
        ]);
    }

    private function postCinetPayWebhook(array $fields)
    {
        return $this->call(
            'POST',
            route('webhooks.psp', ['provider' => 'cinetpay']),
            [], [], [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query($fields)
        );
    }
}
