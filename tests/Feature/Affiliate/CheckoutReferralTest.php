<?php

namespace Tests\Feature\Affiliate;

use App\Models\Product;
use App\Models\User;
use App\Support\ReferralCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutReferralTest extends TestCase
{
    use RefreshDatabase;

    private function digitalProduct(): Product
    {
        return Product::factory()->digitalAffiliable()->create(['price' => 10000]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'mobile_money',
            'payment_provider' => 'fedapay',
            'email' => 'buyer@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_checkout_records_referring_affiliate_from_cookie(): void
    {
        $affiliate = User::factory()->affiliate()->create(['code_parrainage' => 'JEAN125']);
        $product = $this->digitalProduct();

        $this->withCookie(ReferralCookie::NAME, 'JEAN125')
            ->post('/checkout', $this->payload($product))
            ->assertCreated();

        $this->assertDatabaseHas('orders', [
            'referring_affiliate_id' => $affiliate->id,
        ]);
    }

    public function test_checkout_records_referring_affiliate_from_explicit_code(): void
    {
        $affiliate = User::factory()->affiliate()->create(['code_parrainage' => 'PAUL700']);
        $product = $this->digitalProduct();

        $this->post('/checkout', $this->payload($product, ['referral_code' => 'PAUL700']))
            ->assertCreated();

        $this->assertDatabaseHas('orders', ['referring_affiliate_id' => $affiliate->id]);
    }

    public function test_self_referral_is_ignored_at_checkout(): void
    {
        $affiliate = User::factory()->affiliate()->create(['code_parrainage' => 'JEAN125']);
        $product = $this->digitalProduct();

        $this->actingAs($affiliate)
            ->withCookie(ReferralCookie::NAME, 'JEAN125')
            ->post('/checkout', $this->payload($product, ['email' => null]))
            ->assertCreated();

        // §38 — l'acheteur ne peut pas être son propre affilié référent.
        $this->assertDatabaseHas('orders', [
            'user_id' => $affiliate->id,
            'referring_affiliate_id' => null,
        ]);
    }

    public function test_inactive_affiliate_is_not_recorded(): void
    {
        User::factory()->affiliate()->create([
            'code_parrainage' => 'BANNED1',
            'status' => 'suspended',
        ]);
        $product = $this->digitalProduct();

        $this->post('/checkout', $this->payload($product, ['referral_code' => 'BANNED1']))
            ->assertCreated();

        $this->assertDatabaseHas('orders', ['referring_affiliate_id' => null]);
    }
}
