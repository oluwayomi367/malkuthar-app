<?php

namespace App\Contracts;

use App\Models\Withdrawal;
use App\Payments\PayoutResult;

/**
 * §54 — Architecture des payouts : MALKUTHAR -> moteur de retrait ->
 * PRESTATAIRE DE PAYOUT -> Mobile Money / Banque / Wallet.
 *
 * Ajouter/changer un prestataire de payout = implémenter cette interface
 * et l'enregistrer dans PayoutGatewayManager, sans reconstruire le
 * système d'affiliation ni WithdrawalService.
 */
interface PayoutGateway
{
    public function provider(): string;

    /** Ce prestataire sait-il traiter ce moyen (mobile_money, bank_account, e_wallet) pour ce pays ? */
    public function supports(string $method, ?string $country): bool;

    public function sendPayout(Withdrawal $withdrawal): PayoutResult;
}
