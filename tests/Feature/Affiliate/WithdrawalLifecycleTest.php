<?php

namespace Tests\Feature\Affiliate;

use App\Exceptions\WithdrawalException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §84 Tests 9 & 10 — retrait réussi (wallet correctement débité) et
 * retrait échoué (les fonds ne sont pas définitivement perdus).
 */
class WithdrawalLifecycleTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function service(): WithdrawalService
    {
        return app(WithdrawalService::class);
    }

    private function affiliateWithBalance(float $available): array
    {
        $user = $this->affiliate();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'XOF',
            'balance_available' => $available,
        ]);

        return [$user, $wallet];
    }

    private function request(User $user, float $amount = 25000): Withdrawal
    {
        return $this->service()->requestWithdrawal($user, [
            'amount' => $amount,
            'method' => 'mobile_money',
            'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
    }

    public function test_9_successful_withdrawal_debits_wallet_through_ledger(): void
    {
        [$user, $wallet] = $this->affiliateWithBalance(50000);

        $withdrawal = $this->request($user, 25000);

        $this->assertSame('processing', $withdrawal->status);
        $this->assertEqualsWithDelta(25000, (float) $wallet->fresh()->balance_available, 0.001);

        // §48 — le débit est matérialisé par une écriture de ledger.
        $this->assertDatabaseHas('ledger_entries', [
            'withdrawal_id' => $withdrawal->id,
            'source_type' => 'withdrawal',
            'direction' => 'debit',
            'status' => 'processing',
            'amount' => 25000,
        ]);

        $this->service()->markWithdrawalAsPaid($withdrawal, 'PROV-REF-1');

        $withdrawal->refresh();
        $wallet->refresh();
        $this->assertSame('paid', $withdrawal->status);
        $this->assertSame('PROV-REF-1', $withdrawal->provider_reference);
        $this->assertEqualsWithDelta(25000, (float) $wallet->balance_available, 0.001);
        $this->assertEqualsWithDelta(25000, (float) $wallet->total_withdrawn, 0.001);
    }

    public function test_10_failed_withdrawal_returns_the_funds(): void
    {
        [$user, $wallet] = $this->affiliateWithBalance(50000);

        $withdrawal = $this->request($user, 25000);
        $this->assertEqualsWithDelta(25000, (float) $wallet->fresh()->balance_available, 0.001);

        $this->service()->rollbackFailedWithdrawal($withdrawal, 'operator timeout');

        $withdrawal->refresh();
        $wallet->refresh();
        $this->assertSame('failed', $withdrawal->status);
        $this->assertSame('operator timeout', $withdrawal->failure_reason);

        // Les fonds sont revenus (§84 Test 10).
        $this->assertEqualsWithDelta(50000, (float) $wallet->balance_available, 0.001);

        // §49 — écriture de crédit distincte + débit d'origine marqué échoué.
        $this->assertDatabaseHas('ledger_entries', [
            'withdrawal_id' => $withdrawal->id,
            'source_type' => 'withdrawal_failed_refund',
            'direction' => 'credit',
            'status' => 'available',
            'amount' => 25000,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'withdrawal_id' => $withdrawal->id,
            'source_type' => 'withdrawal',
            'direction' => 'debit',
            'status' => 'failed',
        ]);
    }

    public function test_rollback_is_idempotent(): void
    {
        [$user, $wallet] = $this->affiliateWithBalance(50000);
        $withdrawal = $this->request($user, 25000);

        $this->service()->rollbackFailedWithdrawal($withdrawal);
        $this->service()->rollbackFailedWithdrawal($withdrawal->fresh());

        $this->assertSame(1, $wallet->ledgerEntries()->where('source_type', 'withdrawal_failed_refund')->count());
        $this->assertEqualsWithDelta(50000, (float) $wallet->fresh()->balance_available, 0.001);
    }

    public function test_withdrawal_above_available_balance_is_rejected(): void
    {
        [$user, $wallet] = $this->affiliateWithBalance(10000);

        $this->expectException(WithdrawalException::class);
        $this->request($user, 25000);
    }

    public function test_withdrawal_below_configured_minimum_is_rejected(): void
    {
        config(['malkuthar.withdrawals.minimum_amount' => 1000]);
        [$user, $wallet] = $this->affiliateWithBalance(50000);

        $this->expectException(WithdrawalException::class);
        $this->request($user, 500);
    }
}
