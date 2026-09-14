<?php

namespace Tests\Feature\Affiliate;

use App\Models\User;
use App\Models\Wallet;
use App\Services\AffiliateOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateActivationTest extends TestCase
{
    use RefreshDatabase;

    private function onboarding(): AffiliateOnboardingService
    {
        return app(AffiliateOnboardingService::class);
    }

    public function test_activation_generates_sequential_id_code_and_wallet(): void
    {
        $first = $this->onboarding()->activate(User::factory()->create(['first_name' => 'Jean']));
        $second = $this->onboarding()->activate(User::factory()->create(['first_name' => 'Pierre']));

        // §32 — identifiant unique, permanent, séquentiel.
        $this->assertSame('MAL-AFF-000001', $first->affiliate_id);
        $this->assertSame('MAL-AFF-000002', $second->affiliate_id);

        // §34 — code de parrainage unique dérivé du prénom.
        $this->assertStringStartsWith('JEAN', $first->code_parrainage);
        $this->assertNotSame($first->code_parrainage, $second->code_parrainage);

        // §43 — portefeuille créé.
        $this->assertDatabaseHas('wallets', ['user_id' => $first->id]);
        $this->assertSame(1, Wallet::where('user_id', $first->id)->count());

        $this->assertNotNull($first->affiliate_activated_at);
        $this->assertTrue($first->isAffiliate());
    }

    public function test_activation_is_idempotent(): void
    {
        $user = User::factory()->create();

        $a = $this->onboarding()->activate($user);
        $b = $this->onboarding()->activate($user->fresh());

        $this->assertSame($a->affiliate_id, $b->affiliate_id);
        $this->assertSame($a->code_parrainage, $b->code_parrainage);
        $this->assertSame(1, Wallet::where('user_id', $user->id)->count());
    }

    public function test_join_route_activates_the_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('affiliate.activate'))
            ->assertRedirect(route('affiliate.dashboard'));

        $this->assertTrue($user->fresh()->isAffiliate());
    }
}
