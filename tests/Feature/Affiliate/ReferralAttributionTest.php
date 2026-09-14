<?php

namespace Tests\Feature\Affiliate;

use App\Models\User;
use App\Services\AffiliateOnboardingService;
use App\Support\ReferralCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function affiliate(array $attributes = []): User
    {
        return User::factory()->affiliate()->create($attributes);
    }

    public function test_ref_route_sets_attribution_cookie_for_known_code(): void
    {
        $sponsor = $this->affiliate(['code_parrainage' => 'JEAN125']);

        $response = $this->get('/ref/JEAN125');

        $response->assertRedirect('/');
        $response->assertCookie(ReferralCookie::NAME, 'JEAN125');
    }

    public function test_ref_route_ignores_unknown_code(): void
    {
        $response = $this->get('/ref/NOPE999');

        $response->assertRedirect('/');
        $response->assertCookieMissing(ReferralCookie::NAME);
    }

    public function test_registration_with_referral_cookie_attaches_sponsor(): void
    {
        $sponsor = $this->affiliate(['code_parrainage' => 'JEAN125']);

        $this->withCookie(ReferralCookie::NAME, 'JEAN125')
            ->post('/register', $this->registrationPayload())
            ->assertRedirect(route('account.dashboard'));

        $newUser = User::where('email', 'newbie@example.com')->firstOrFail();
        $this->assertSame($sponsor->id, $newUser->parent_id); // §35
    }

    public function test_registration_with_explicit_referral_code_field(): void
    {
        $sponsor = $this->affiliate(['code_parrainage' => 'PIERRE300']);

        $this->post('/register', $this->registrationPayload(['referral_code' => 'PIERRE300']))
            ->assertRedirect(route('account.dashboard'));

        $this->assertSame(
            $sponsor->id,
            User::where('email', 'newbie@example.com')->value('parent_id')
        );
    }

    public function test_sponsor_is_immutable_once_set(): void
    {
        $onboarding = app(AffiliateOnboardingService::class);
        $first = $this->affiliate();
        $second = $this->affiliate();
        $user = User::factory()->create();

        $this->assertTrue($onboarding->attributeSponsor($user, $first));
        $this->assertFalse($onboarding->attributeSponsor($user->fresh(), $second)); // §35

        $this->assertSame($first->id, $user->fresh()->parent_id);
    }

    public function test_self_referral_is_blocked(): void
    {
        $onboarding = app(AffiliateOnboardingService::class);
        $affiliate = $this->affiliate();

        $this->assertFalse($onboarding->attributeSponsor($affiliate, $affiliate)); // §38
        $this->assertNull($affiliate->fresh()->parent_id);
    }

    public function test_suspended_sponsor_is_not_attached(): void
    {
        $onboarding = app(AffiliateOnboardingService::class);
        $sponsor = $this->affiliate(['status' => 'suspended']);
        $user = User::factory()->create();

        $this->assertFalse($onboarding->attributeSponsor($user, $sponsor));
        $this->assertNull($user->fresh()->parent_id);
    }

    public function test_cycle_is_prevented(): void
    {
        $onboarding = app(AffiliateOnboardingService::class);

        $a = $this->affiliate();
        $b = $this->affiliate(['parent_id' => $a->id]);
        $c = $this->affiliate(['parent_id' => $b->id]);

        // Rattacher A à C fermerait la boucle A -> ... -> C -> A.
        $this->assertFalse($onboarding->attributeSponsor($a, $c)); // §38
        $this->assertNull($a->fresh()->parent_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'New',
            'last_name' => 'Bie',
            'email' => 'newbie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }
}
