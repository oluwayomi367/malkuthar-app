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

    public function test_client_registration_locks_referral_code_when_visiting_with_cookie(): void
    {
        $this->affiliate(['code_parrainage' => 'JEAN125']);

        $response = $this->withCookie(ReferralCookie::NAME, 'JEAN125')
            ->get(route('register.client'))
            ->assertOk();

        $response->assertSee('readonly');
        $response->assertSee('JEAN125');
        $response->assertSee('Lien détecté — Verrouillé');
        $response->assertSee('Non modifiable');
    }

    public function test_client_registration_locks_referral_code_when_visiting_with_ref_query_param(): void
    {
        $this->affiliate(['code_parrainage' => 'JEAN125']);

        $response = $this->get('/register?ref=JEAN125')
            ->assertOk();

        $response->assertSee('readonly');
        $response->assertSee('JEAN125');
        $response->assertSee('Non modifiable');
        $response->assertCookie(ReferralCookie::NAME, 'JEAN125');
    }

    public function test_registration_allows_editing_when_no_referral_link(): void
    {
        $response = $this->get(route('register.client'))->assertOk();

        $response->assertDontSee('Lien détecté — Verrouillé');
        $response->assertDontSee('Non modifiable');
    }

    public function test_affiliate_registration_locks_referral_code_when_link_used(): void
    {
        $this->affiliate(['code_parrainage' => 'JEAN125']);

        $response = $this->get(route('register.affiliate', ['ref' => 'JEAN125']))
            ->assertOk();

        $response->assertSee('readonly');
        $response->assertSee('JEAN125');
        $response->assertSee('Non modifiable');
    }

    public function test_vendor_registration_locks_referral_code_when_link_used(): void
    {
        \App\Models\Setting::put('marketplace.enabled', '1');
        $this->affiliate(['code_parrainage' => 'JEAN125']);

        $response = $this->get(route('register.vendor', ['ref' => 'JEAN125']))
            ->assertOk();

        $response->assertSee('readonly');
        $response->assertSee('JEAN125');
        $response->assertSee('Non modifiable');
    }

    public function test_sponsor_from_cookie_is_protected_against_client_tampering(): void
    {
        $sponsor1 = $this->affiliate(['code_parrainage' => 'JEAN125']);
        $sponsor2 = $this->affiliate(['code_parrainage' => 'TAMPERED99']);

        // Le client a le cookie JEAN125 mais essaie d'envoyer TAMPERED99
        $this->withCookie(ReferralCookie::NAME, 'JEAN125')
            ->post('/register', $this->registrationPayload(['referral_code' => 'TAMPERED99']))
            ->assertRedirect(route('account.dashboard'));

        $newUser = User::where('email', 'newbie@example.com')->firstOrFail();
        $this->assertSame($sponsor1->id, $newUser->parent_id);
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
