<?php

namespace Tests\Feature\Admin;

use App\Models\CommissionCampaign;
use App\Models\User;
use App\Services\CommissionRateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §27 — l'administration crée / active / supprime des campagnes de taux.
 */
class AdminCommissionCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_reach_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/commission-campaigns')
            ->assertForbidden();
    }

    public function test_page_shows_the_currently_applied_rates(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/commission-campaigns')
            ->assertOk()
            ->assertSee('Taux appliqués en ce moment')
            ->assertSee('15 % · 10 % · 5 %');
    }

    public function test_admin_creates_a_campaign_in_percent_and_it_becomes_effective(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/commission-campaigns')
            ->post('/admin/commission-campaigns', [
                'name' => 'Campagne B',
                'rate_l1' => 20, 'rate_l2' => 7, 'rate_l3' => 3,
                'priority' => 0, 'is_default' => '1',
            ])
            ->assertRedirect('/admin/commission-campaigns')
            ->assertSessionHas('success');

        $campaign = CommissionCampaign::firstOrFail();
        $this->assertSame('0.2000', $campaign->rate_l1);   // stocké en fraction
        $this->assertTrue($campaign->is_default);

        $this->assertSame([1 => 0.20, 2 => 0.07, 3 => 0.03], app(CommissionRateResolver::class)->rates());
    }

    public function test_making_a_campaign_default_clears_the_others(): void
    {
        $a = CommissionCampaign::factory()->default()->create(['name' => 'A']);
        $b = CommissionCampaign::factory()->create(['name' => 'B']);

        $this->actingAs($this->admin())
            ->put("/admin/commission-campaigns/{$b->id}", ['action' => 'make_default'])
            ->assertRedirect();

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
    }

    public function test_a_used_campaign_cannot_be_deleted(): void
    {
        $campaign = CommissionCampaign::factory()->create();

        $affiliate = User::factory()->affiliate()->create();
        $wallet = \App\Models\Wallet::create(['user_id' => $affiliate->id, 'currency' => 'XOF']);
        \App\Models\LedgerEntry::create([
            'wallet_id' => $wallet->id, 'user_id' => $affiliate->id,
            'reference' => 'COM-TEST-1', 'direction' => 'credit', 'source_type' => 'commission',
            'status' => 'pending', 'amount' => 1500, 'currency' => 'XOF',
            'level' => 1, 'commission_rate' => 0.15, 'commission_campaign_id' => $campaign->id,
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/commission-campaigns')
            ->delete("/admin/commission-campaigns/{$campaign->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('campaign');

        $this->assertDatabaseHas('commission_campaigns', ['id' => $campaign->id]);
    }
}
