<?php

namespace App\Payments;

/**
 * Résultat d'une tentative d'envoi de fonds à un affilié (§54).
 *
 *  - SENT     : le prestataire confirme l'envoi -> le retrait peut être
 *               marqué payé.
 *  - FAILED   : le prestataire refuse -> le retrait doit être annulé et
 *               les fonds réinjectés (§10 Test 10).
 *  - MANUAL   : aucune automatisation disponible pour ce moyen/pays ->
 *               le retrait reste PROCESSING, traité manuellement par
 *               MALKUTHAR (§51 : "lorsque le système de payout permet
 *               l'automatisation" — sinon, on ne perd jamais les fonds).
 */
final class PayoutResult
{
    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const MANUAL = 'manual';

    private function __construct(
        public readonly string $status,
        public readonly ?string $providerReference = null,
        public readonly ?string $reason = null,
    ) {}

    public static function sent(string $providerReference): self
    {
        return new self(self::SENT, providerReference: $providerReference);
    }

    public static function failed(string $reason): self
    {
        return new self(self::FAILED, reason: $reason);
    }

    public static function manual(string $reason = 'Automatisation indisponible pour ce moyen/pays.'): self
    {
        return new self(self::MANUAL, reason: $reason);
    }
}
