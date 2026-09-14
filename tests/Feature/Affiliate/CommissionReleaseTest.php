<?php

namespace Tests\Feature\Affiliate;

use App\Models\LedgerEntry;
use App\Services\AffiliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §40-§42 — période de validation puis passage PENDING -> AVAILABLE.
 */
class CommissionReleaseTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function service(): AffiliationService
    {
        return app(AffiliationService::class);
    }

    public function test_commission_is_not_released_before_the_validation_delay(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 14]);
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        $this->assertSame(0, $this->service()->releaseDueCommissions());

        $wallet = $jean->wallet->fresh();
        $this->assertEqualsWithDelta(1500, (float) $wallet->balance_pending, 0.001);
        $this->assertEqualsWithDelta(0, (float) $wallet->balance_available, 0.001);
    }

    public function test_commission_becomes_available_after_the_delay_and_updates_wallet(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 14]);
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        $this->travel(15)->days();

        $this->assertSame(1, $this->service()->releaseDueCommissions());

        $wallet = $jean->wallet->fresh();
        $this->assertEqualsWithDelta(0, (float) $wallet->balance_pending, 0.001);
        $this->assertEqualsWithDelta(1500, (float) $wallet->balance_available, 0.001); // §43
        $this->assertEqualsWithDelta(1500, (float) $wallet->total_earned, 0.001);

        $this->assertSame('available', LedgerEntry::where('source_type', 'commission')->first()->status);

        // §11-§12 — le cache reste cohérent avec le ledger.
        $this->assertEqualsWithDelta(
            1500,
            $wallet->computeAvailableBalanceFromLedger(),
            0.001
        );
    }

    public function test_release_is_idempotent(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        $this->assertSame(1, $this->service()->releaseDueCommissions());
        $this->assertSame(0, $this->service()->releaseDueCommissions());

        $this->assertEqualsWithDelta(1500, (float) $jean->wallet->fresh()->balance_available, 0.001);
    }

    public function test_scheduled_command_releases_commissions(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        $this->artisan('affiliate:release-commissions')
            ->expectsOutputToContain('1 commission')
            ->assertSuccessful();
    }
}
