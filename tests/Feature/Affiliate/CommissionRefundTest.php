<?php

namespace Tests\Feature\Affiliate;

use App\Models\LedgerEntry;
use App\Services\AffiliationService;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §80 / §84 Test 6 — le remboursement reprend correctement les commissions.
 */
class CommissionRefundTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function affiliation(): AffiliationService
    {
        return app(AffiliationService::class);
    }

    private function refunds(): OrderRefundService
    {
        return app(OrderRefundService::class);
    }

    public function test_refund_cancels_pending_commissions(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 14]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);

        $this->affiliation()->distributeCommissions($order);
        $this->refunds()->refund($order->fresh());

        $this->assertSame('cancelled', LedgerEntry::where('source_type', 'commission')->first()->status);

        $wallet = $jean->wallet->fresh();
        $this->assertEqualsWithDelta(0, (float) $wallet->balance_pending, 0.001);
        $this->assertEqualsWithDelta(0, (float) $wallet->balance_available, 0.001);
    }

    public function test_refund_reverses_available_commissions_with_a_dedicated_entry(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);

        $this->affiliation()->distributeCommissions($order);
        $this->affiliation()->releaseDueCommissions(); // -> available, total_earned = 1500

        $this->refunds()->refund($order->fresh());

        // §49 — écriture de reprise distincte, pas de modification en place.
        $this->assertDatabaseHas('ledger_entries', [
            'source_type' => 'commission_reversal',
            'direction' => 'debit',
            'amount' => 1500,
            'status' => 'reversed',
        ]);
        $this->assertSame('reversed', LedgerEntry::where('source_type', 'commission')->first()->status);

        $wallet = $jean->wallet->fresh();
        $this->assertEqualsWithDelta(0, (float) $wallet->balance_available, 0.001);
        $this->assertEqualsWithDelta(0, (float) $wallet->total_earned, 0.001);
    }

    public function test_refund_is_idempotent(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);

        $this->affiliation()->distributeCommissions($order);
        $this->affiliation()->releaseDueCommissions();

        $this->assertTrue($this->refunds()->refund($order->fresh()));
        $this->assertFalse($this->refunds()->refund($order->fresh()));

        $this->assertSame(1, LedgerEntry::where('source_type', 'commission_reversal')->count());
        $this->assertEqualsWithDelta(0, (float) $jean->wallet->fresh()->balance_available, 0.001);
    }
}
