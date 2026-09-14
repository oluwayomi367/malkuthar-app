<?php

namespace Tests\Feature\Auth;

use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_admin_lands_on_the_admin_dashboard(): void
    {
        $this->login(User::factory()->admin()->create())
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_active_vendor_lands_on_the_vendor_dashboard(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $this->login($vendor->user)->assertRedirect(route('vendor.dashboard'));
    }

    public function test_pending_vendor_lands_on_the_application_status(): void
    {
        $vendor = VendorProfile::factory()->create(); // pending
        $this->login($vendor->user)->assertRedirect(route('vendor.application.status'));
    }

    public function test_affiliate_lands_on_the_affiliate_dashboard(): void
    {
        $this->login(User::factory()->affiliate()->create())
            ->assertRedirect(route('affiliate.dashboard'));
    }

    public function test_plain_client_lands_on_the_account_dashboard(): void
    {
        $this->login(User::factory()->create())
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_intended_url_still_wins_over_the_profile_home(): void
    {
        $user = User::factory()->create();

        // Une page protégée mémorise l'URL voulue.
        $this->get('/mon-compte/profil')->assertRedirect(route('login'));
        $this->login($user)->assertRedirect(route('account.profile'));
    }
}
