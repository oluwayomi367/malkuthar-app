<?php

namespace Tests\Feature\Affiliate;

use App\Exceptions\WithdrawalException;
use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\Wallet;
use App\Services\AffiliateWithdrawalMethodService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * §51, §54, §56, §57 — frais, plafond, moteur de payout, sécurité du
 * changement de moyen de retrait.
 */
class WithdrawalSecurityTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function affiliateWithBalance(float $available): array
    {
        $user = $this->affiliate();
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'XOF', 'balance_available' => $available]);

        return [$user, $wallet];
    }

    public function test_fixed_fee_is_deducted_into_net_amount_but_wallet_debited_gross(): void
    {
        config(['malkuthar.withdrawals.fee' => ['type' => 'fixed', 'fixed' => 500]]);
        [$user, $wallet] = $this->affiliateWithBalance(50000);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($user, [
            'amount' => 25000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);

        $this->assertEqualsWithDelta(500, (float) $withdrawal->fee, 0.001);
        $this->assertEqualsWithDelta(24500, (float) $withdrawal->net_amount, 0.001);
        // §48 - le wallet est débité du montant brut demandé, pas du net.
        $this->assertEqualsWithDelta(25000, (float) $wallet->fresh()->balance_available, 0.001);
    }

    public function test_percent_fee_is_computed_on_amount(): void
    {
        config(['malkuthar.withdrawals.fee' => ['type' => 'percent', 'percent' => 2]]);
        [$user] = $this->affiliateWithBalance(50000);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($user, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);

        $this->assertEqualsWithDelta(200, (float) $withdrawal->fee, 0.001);
        $this->assertEqualsWithDelta(9800, (float) $withdrawal->net_amount, 0.001);
    }

    public function test_withdrawal_above_configured_maximum_is_rejected(): void
    {
        config(['malkuthar.withdrawals.maximum_amount' => 20000]);
        [$user] = $this->affiliateWithBalance(50000);

        $this->expectException(WithdrawalException::class);
        app(WithdrawalService::class)->requestWithdrawal($user, [
            'amount' => 25000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
    }

    public function test_cooldown_blocks_withdrawal_right_after_a_method_change(): void
    {
        config(['malkuthar.withdrawals.cooldown_after_account_change_hours' => 24]);
        [$user, $wallet] = $this->affiliateWithBalance(50000);
        $wallet->update(['withdrawal_method_changed_at' => now()->subHours(2)]);

        $this->expectException(WithdrawalException::class);
        app(WithdrawalService::class)->requestWithdrawal($user, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
    }

    public function test_withdrawal_is_allowed_again_after_cooldown_elapses(): void
    {
        config(['malkuthar.withdrawals.cooldown_after_account_change_hours' => 24]);
        [$user, $wallet] = $this->affiliateWithBalance(50000);
        $wallet->update(['withdrawal_method_changed_at' => now()->subHours(25)]);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($user, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);

        $this->assertSame('processing', $withdrawal->status);
    }

    public function test_manual_payout_gateway_leaves_withdrawal_processing(): void
    {
        [$user] = $this->affiliateWithBalance(50000);

        $response = $this->actingAs($user)->postJson('/affiliate/withdrawals', [
            'amount' => 10000,
            'method' => 'mobile_money',
            'mobile_money_number' => '+22990000000',
        ]);

        $response->assertCreated();
        $this->assertSame('processing', $response->json('data.status'));
    }

    // ----- §56 : changement de moyen de retrait protégé par OTP -----

    public function test_withdrawal_method_change_requires_a_valid_otp(): void
    {
        Mail::fake();
        $user = $this->affiliate();
        Wallet::create(['user_id' => $user->id, 'currency' => 'XOF']);

        $this->actingAs($user)->postJson('/affiliate/withdrawal-method', [
            'method' => 'mobile_money',
            'mobile_money_number' => '+22991112233',
        ])->assertOk();

        Mail::assertQueued(OtpCodeMail::class);
        $code = OtpCode::where('user_id', $user->id)->latest('created_at')->first();
        $this->assertNotNull($code);

        // Mauvais code -> rejeté, rien n'est enregistré.
        $this->actingAs($user)->postJson('/affiliate/withdrawal-method/confirm', ['code' => '000000'])
            ->assertStatus(422);
        $this->assertNull($user->wallet->fresh()->default_withdrawal_method);
    }

    public function test_correct_otp_applies_the_change_and_starts_the_cooldown(): void
    {
        $user = $this->affiliate();
        Wallet::create(['user_id' => $user->id, 'currency' => 'XOF']);

        $code = app(AffiliateWithdrawalMethodService::class)->requestChange($user, [
            'method' => 'mobile_money',
            'account_details' => ['mobile_money_number' => '+22991112233', 'operator' => 'mtn'],
        ]);

        $this->actingAs($user)
            ->postJson('/affiliate/withdrawal-method/confirm', ['code' => $code])
            ->assertOk();

        $wallet = $user->wallet->fresh();
        $this->assertSame('mobile_money', $wallet->default_withdrawal_method);
        $this->assertNotNull($wallet->withdrawal_method_changed_at);
    }

    public function test_otp_cannot_be_reused(): void
    {
        $user = $this->affiliate();
        Wallet::create(['user_id' => $user->id, 'currency' => 'XOF']);

        $code = app(AffiliateWithdrawalMethodService::class)->requestChange($user, [
            'method' => 'mobile_money',
            'account_details' => ['mobile_money_number' => '+22991112233'],
        ]);

        app(AffiliateWithdrawalMethodService::class)->confirmChange($user, $code);

        $this->expectException(WithdrawalException::class);
        app(AffiliateWithdrawalMethodService::class)->confirmChange($user, $code);
    }
}
