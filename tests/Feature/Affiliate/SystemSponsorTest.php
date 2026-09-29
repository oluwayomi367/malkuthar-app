<?php

namespace Tests\Feature\Affiliate;

use App\Models\Product;
use App\Models\User;
use App\Services\AffiliateOnboardingService;
use App\Services\AffiliationService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSponsorTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_without_sponsor_assigns_system_as_sponsor(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Alice',
            'last_name' => 'SansParrain',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'alice@example.com')->firstOrFail();
        $system = app(AffiliateOnboardingService::class)->systemUser();

        $this->assertNotNull($user->parent_id);
        $this->assertSame($system->id, $user->parent_id);
    }

    public function test_checkout_without_referral_assigns_system_or_buyer_sponsor(): void
    {
        $system = app(AffiliateOnboardingService::class)->systemUser();
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $checkoutService = app(CheckoutService::class);

        // Achat invité sans parrain
        $order = $checkoutService->createOrderFromCheckout(
            data: [
                'payment_method' => 'mobile_money',
                'payment_provider' => 'fedapay',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ],
            user: null,
            productsById: collect([$product->id => $product]),
            referringAffiliate: null,
        );

        $this->assertSame($system->id, $order->referring_affiliate_id);

        // Simulation du paiement
        $order->update(['payment_status' => 'paid']);

        app(AffiliationService::class)->distributeCommissions($order);

        // Le wallet du Système doit avoir reçu la commission L1 (15%) en attente
        $systemWallet = $system->wallet()->first();
        $this->assertNotNull($systemWallet);
        $this->assertEquals(1500.0, (float) $systemWallet->balance_pending);
    }
}
