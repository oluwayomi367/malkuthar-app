<?php

namespace App\Services;

use App\Exceptions\WithdrawalException;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AffiliateWithdrawalMethodService — modification du moyen de retrait par
 * défaut de l'affilié, protégée par OTP (§56).
 *
 * Flux : requestChange() envoie un code, stocke la modification demandée
 * en attente (cache, TTL 10 min) ; confirmChange() vérifie le code puis
 * applique réellement le changement et déclenche le cooldown anti-fraude
 * consommé par WithdrawalService.
 */
class AffiliateWithdrawalMethodService
{
    private const OTP_PURPOSE = 'withdrawal_method_change';

    public function __construct(private readonly OtpService $otp) {}

    /**
     * @param  array{method: string, account_details: array}  $data
     * @return string le code OTP généré (à envoyer par email/SMS par l'appelant)
     */
    public function requestChange(User $affiliate, array $data): string
    {
        Cache::put($this->pendingKey($affiliate), $data, now()->addMinutes(10));

        return $this->otp->issue($affiliate, self::OTP_PURPOSE);
    }

    /**
     * @throws WithdrawalException si le code est invalide/expiré ou
     *                             qu'aucune modification n'est en attente.
     */
    public function confirmChange(User $affiliate, string $code): Wallet
    {
        if (! $this->otp->verify($affiliate, self::OTP_PURPOSE, $code)) {
            throw WithdrawalException::invalidOtp();
        }

        $pending = Cache::pull($this->pendingKey($affiliate));

        if (! $pending) {
            throw WithdrawalException::invalidOtp();
        }

        return DB::transaction(function () use ($affiliate, $pending) {
            $wallet = Wallet::query()
                ->firstOrCreate(['user_id' => $affiliate->id], ['currency' => $affiliate->currency ?? 'XOF']);

            $wallet->update([
                'default_withdrawal_method' => $pending['method'],
                'default_withdrawal_account' => $pending['account_details'],
                // §56 - déclenche le délai de sécurité côté WithdrawalService.
                'withdrawal_method_changed_at' => now(),
            ]);

            return $wallet->refresh();
        });
    }

    private function pendingKey(User $affiliate): string
    {
        return "withdrawal-method-change:{$affiliate->id}";
    }
}
