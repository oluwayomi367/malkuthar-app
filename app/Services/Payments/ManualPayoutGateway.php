<?php

namespace App\Services\Payments;

use App\Contracts\PayoutGateway;
use App\Models\Withdrawal;
use App\Payments\PayoutResult;

/**
 * §54 — Prestataire de payout par défaut : aucune automatisation, le
 * retrait reste PROCESSING et est traité manuellement par MALKUTHAR
 * (§51 : l'automatisation n'est activée que "lorsque le système de
 * payout le permet" — tant qu'aucun prestataire réel n'est branché,
 * les fonds ne sont jamais perdus, juste traités à la main).
 */
class ManualPayoutGateway implements PayoutGateway
{
    public function provider(): string
    {
        return 'manual';
    }

    public function supports(string $method, ?string $country): bool
    {
        return true; // filet de sécurité universel
    }

    public function sendPayout(Withdrawal $withdrawal): PayoutResult
    {
        return PayoutResult::manual();
    }
}
