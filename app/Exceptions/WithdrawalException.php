<?php

namespace App\Exceptions;

use Illuminate\Support\Carbon;
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

    public static function aboveMaximumAmount(float $requested, float $maximum): self
    {
        return new self(sprintf(
            'Le montant maximum de retrait est de %s (demandé : %s).',
            number_format($maximum, 2),
            number_format($requested, 2)
        ));
    }

    public static function cooldownActive(Carbon $availableAt): self
    {
        return new self(sprintf(
            'Retrait temporairement bloqué suite à une modification récente de votre moyen de retrait (§56). '.
            'Nouvelle tentative possible à partir du %s.',
            $availableAt->format('d/m/Y H:i')
        ));
    }

    public static function accountNotActive(string $status): self
    {
        return new self("Votre compte affilié n'est pas actif (statut : {$status}) — les retraits sont temporairement indisponibles.");
    }

    public static function invalidOtp(): self
    {
        return new self('Code de vérification invalide ou expiré.');
    }

    public static function alreadyFinalized(string $withdrawalId, string $status): self
    {
        return new self("Le retrait [{$withdrawalId}] a déjà été finalisé (statut: {$status}) — opération ignorée.");
    }
}
