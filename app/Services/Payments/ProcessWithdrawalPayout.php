<?php

namespace App\Services\Payments;

use App\Models\Withdrawal;
use App\Payments\PayoutResult;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Log;

/**
 * §54 — Orchestre l'envoi effectif des fonds pour un retrait PROCESSING :
 * interroge le PayoutGatewayManager puis répercute le résultat sur le
 * wallet via WithdrawalService (jamais d'écriture directe ici).
 */
class ProcessWithdrawalPayout
{
    public function __construct(
        private readonly PayoutGatewayManager $gateways,
        private readonly WithdrawalService $withdrawals,
    ) {}

    public function process(Withdrawal $withdrawal): void
    {
        if ($withdrawal->status !== 'processing') {
            return;
        }

        $gateway = $this->gateways->for($withdrawal);
        $result = $gateway->sendPayout($withdrawal);

        match ($result->status) {
            PayoutResult::SENT => $this->withdrawals->markWithdrawalAsPaid($withdrawal, $result->providerReference),
            PayoutResult::FAILED => $this->withdrawals->rollbackFailedWithdrawal($withdrawal, $result->reason),
            PayoutResult::MANUAL => Log::info('ProcessWithdrawalPayout: retrait laissé en traitement manuel.', [
                'withdrawal_id' => $withdrawal->id,
                'provider' => $gateway->provider(),
                'reason' => $result->reason,
            ]),
            default => null,
        };
    }
}
