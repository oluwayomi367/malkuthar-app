<?php

namespace Tests\Feature\Affiliate;

use App\Models\CommissionCampaign;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\AffiliationService;
use App\Services\CommissionRateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * §27 — taux de commission configurables. Standards 15 / 10 / 5, mais
 * l'architecture accepte des campagnes (ex. B : 20 / 7 / 3) sans
 * reconstruction du moteur.
 */
class CommissionCampaignTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function resolver(): CommissionRateResolver
    {
        return app(CommissionRateResolver::class);
    }

    public function test_resolver_falls_back_to_standard_rates_without_any_campaign(): void
    {
        $this->assertSame([1 => 0.15, 2 => 0.10, 3 => 0.05], $this->resolver()->rates());
    }

    public function test_default_campaign_overrides_the_standard_rates(): void
    {
        CommissionCampaign::factory()->rates(0.20, 0.07, 0.03)->default()->create(['name' => 'Campagne B']);

        $r = $this->resolver()->resolve();
        $this->assertSame('Campagne B', $r['campaign']->name);
        $this->assertSame([1 => 0.20, 2 => 0.07, 3 => 0.03], $r['rates']);
    }

    public function test_active_dated_campaign_wins_and_only_within_its_window(): void
    {
        CommissionCampaign::factory()->rates(0.10, 0.10, 0.05)->create([
            'name' => 'Campagne C',
            'starts_at' => Carbon::parse('2026-01-01'),
            'ends_at' => Carbon::parse('2026-01-31'),
            'priority' => 5,
        ]);

        $this->travelTo(Carbon::parse('2026-01-15'), function () {
            $this->assertSame([1 => 0.10, 2 => 0.10, 3 => 0.05], $this->resolver()->rates());
        });

        $this->travelTo(Carbon::parse('2026-03-01'), function () {
            $this->assertSame([1 => 0.15, 2 => 0.10, 3 => 0.05], $this->resolver()->rates());
        });
    }

    public function test_highest_priority_active_campaign_wins(): void
    {
        CommissionCampaign::factory()->rates(0.12, 0.08, 0.04)->create(['priority' => 1]);
        CommissionCampaign::factory()->rates(0.20, 0.07, 0.03)->create(['priority' => 9]);

        $this->assertSame([1 => 0.20, 2 => 0.07, 3 => 0.03], $this->resolver()->rates());
    }

    public function test_distribution_uses_campaign_rates_and_snapshots_them_on_the_entry(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        CommissionCampaign::factory()->rates(0.20, 0.07, 0.03)->default()->create(['name' => 'Campagne B']);

        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $entry = LedgerEntry::where('order_id', $order->id)->where('level', 1)->firstOrFail();

        // 20 % de 10 000 = 2 000, et le taux 0.2000 est figé sur l'écriture.
        $this->assertSame('2000.00', $entry->amount);
        $this->assertSame('0.2000', $entry->commission_rate);
        $this->assertNotNull($entry->commission_campaign_id);
    }

    public function test_existing_commissions_keep_their_rate_after_a_campaign_change(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);

        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh()); // 15 % -> 1 500

        // Une campagne 20/7/3 est activée APRÈS coup.
        CommissionCampaign::factory()->rates(0.20, 0.07, 0.03)->default()->create();

        $entry = LedgerEntry::where('order_id', $order->id)->where('level', 1)->firstOrFail();
        $this->assertSame('1500.00', $entry->amount);      // inchangé
        $this->assertSame('0.1500', $entry->commission_rate);
    }
}
