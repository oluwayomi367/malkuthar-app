<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exceptions métier levées par AffiliationService.
 */
class CommissionDistributionException extends RuntimeException
{
    public static function orderNotPaid(string $orderId): self
    {
        return new self(
            "Impossible de distribuer les commissions : la commande [{$orderId}] n'est pas au statut payment_status=paid."
        );
    }

    public static function orderHasNoEligibleAffiliate(string $orderId): self
    {
        return new self(
            "La commande [{$orderId}] n'a pas d'affilié référent (referring_affiliate_id) — aucune commission à distribuer."
        );
    }
}
