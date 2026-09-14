<?php

namespace Tests\Feature\Marketplace;

use App\Models\AuditLog;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Notifications\Marketplace\VendorAccountSuspended;
use App\Notifications\Marketplace\VendorApplicationApproved;
use App\Notifications\Marketplace\VendorApplicationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    public function test_the_vendor_surface_is_hidden_while_the_flag_is_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/vendeur/devenir')->assertNotFound();
        $this->actingAs($user)->get('/vendeur')->assertNotFound();
    }

    public function test_a_user_can_submit_a_vendor_application(): void
    {
        $this->enableMarketplace();
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/vendeur/devenir')->assertOk()->assertSee('Ouvrir un espace vendeur');

        $this->actingAs($user)->post('/vendeur/devenir', [
            'display_name' => 'Atelier Lumière',
            'bio' => 'Séances de sophrologie et ateliers de respiration à Cotonou depuis 2019.',
            'region' => 'Littoral',
            'city' => 'Cotonou',
            'accept_terms' => '1',
        ])->assertRedirect(route('vendor.application.status'));

        $profile = VendorProfile::first();
        $this->assertNotNull($profile);
        $this->assertSame('pending', $profile->status);
        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame('atelier-lumiere', $profile->slug);
        $this->assertDatabaseHas('audit_logs', ['action' => 'marketplace.vendor.applied']);
    }

    public function test_application_requires_terms_and_a_real_bio(): void
    {
        $this->enableMarketplace();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/vendeur/devenir', [
            'display_name' => 'X',
            'bio' => 'trop court',
            'region' => '',
        ])->assertSessionHasErrors(['display_name', 'bio', 'region', 'accept_terms']);

        $this->assertDatabaseCount('vendor_profiles', 0);
    }

    public function test_pending_vendor_cannot_reach_the_vendor_space(): void
    {
        $this->enableMarketplace();
        $profile = VendorProfile::factory()->create(); // pending

        $this->actingAs($profile->user)->get('/vendeur')->assertForbidden();
    }

    public function test_admin_approves_a_vendor_who_then_reaches_the_space(): void
    {
        $this->enableMarketplace();
        Notification::fake();
        $admin = $this->admin();
        $profile = VendorProfile::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.marketplace.vendors.approve', $profile))
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame('active', $profile->status);
        $this->assertSame($admin->id, $profile->approved_by);
        Notification::assertSentTo($profile->user, VendorApplicationApproved::class);

        $this->actingAs($profile->user)->get('/vendeur')->assertOk()->assertSee($profile->display_name);
        $this->assertTrue($profile->user->fresh()->isVendor());
    }

    public function test_admin_rejects_with_a_reason(): void
    {
        $this->enableMarketplace();
        Notification::fake();
        $admin = $this->admin();
        $profile = VendorProfile::factory()->create();

        $this->actingAs($admin)->post(route('admin.marketplace.vendors.reject', $profile), [
            'reason' => 'Justificatifs incomplets.',
        ])->assertRedirect();

        $this->assertSame('closed', $profile->fresh()->status);
        Notification::assertSentTo($profile->user, VendorApplicationRejected::class);
    }

    public function test_admin_suspends_and_reactivates_a_vendor(): void
    {
        $this->enableMarketplace();
        Notification::fake();
        $admin = $this->admin();
        $profile = VendorProfile::factory()->active()->create();
        $vendorId = $profile->user_id;

        $this->actingAs($admin)->post(route('admin.marketplace.vendors.suspend', $profile), [
            'reason' => 'Activité suspecte signalée.',
        ])->assertRedirect();

        $this->assertSame('suspended', $profile->fresh()->status);
        Notification::assertSentTo($profile->user, VendorAccountSuspended::class);
        $this->actingAs(User::find($vendorId))->get('/vendeur')->assertForbidden();

        $this->actingAs($admin)->post(route('admin.marketplace.vendors.reactivate', $profile))->assertRedirect();
        $this->assertSame('active', $profile->fresh()->status);
        $this->actingAs(User::find($vendorId))->get('/vendeur')->assertOk();
    }

    public function test_admin_sets_a_vendor_commission_override(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $profile = VendorProfile::factory()->active()->create();

        $this->actingAs($admin)->put(route('admin.marketplace.vendors.commission', $profile), [
            'commission_rate_percent' => 12.5,
        ])->assertRedirect();

        $this->assertSame('0.1250', (string) $profile->fresh()->commission_rate_override);

        $this->actingAs($admin)->put(route('admin.marketplace.vendors.commission', $profile), [
            'commission_rate_percent' => '',
        ])->assertRedirect();
        $this->assertNull($profile->fresh()->commission_rate_override);
    }

    public function test_non_admin_cannot_manage_vendors(): void
    {
        $this->enableMarketplace();
        $profile = VendorProfile::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.marketplace.vendors.approve', $profile))
            ->assertForbidden();
    }

    public function test_vendor_can_update_their_shop_profile(): void
    {
        $this->enableMarketplace();
        $profile = VendorProfile::factory()->active()->create();

        $this->actingAs($profile->user)->put(route('vendor.profile.update'), [
            'display_name' => 'Atelier Lumière — Cotonou',
            'bio' => 'Nouvelle présentation détaillée de notre activité de bien-être et de respiration.',
            'region' => 'Littoral',
            'city' => 'Cotonou',
        ])->assertRedirect();

        $this->assertSame('Atelier Lumière — Cotonou', $profile->fresh()->display_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'marketplace.vendor.profile_updated']);
    }
}
