<?php

namespace App\Exceptions;

use RuntimeException;

class WithdrawalException extends RuntimeException
{
    public static function insufficientBalance(float $requested, float $available): self
    {
        return new self(sprintf(
            'Solde disponible insuffisant : demandé %s, disponible %s.',
            number_format($requested, 2),
            number_format($available, 2)
        ));
    }

    public static function belowMinimumAmount(float $requested, float $minimum): self
    {
        return new self(sprintf(
            'Le montant minimum de retrait est de %s (demandé : %s).',
            number_format($minimum, 2),
            number_format($requested, 2)
        ));
    }

    public static function alreadyFinalized(string $withdrawalId, string $status): self
    {
        return new self("Le retrait [{$withdrawalId}] a déjà été finalisé (statut: {$status}) — opération ignorée.");
    }
}
