<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exceptions métier levées lors du traitement d'un webhook PSP
 * (FedaPay, CinetPay, ...). Chaque factory associe un code HTTP adapté
 * pour la réponse renvoyée au prestataire.
 */
class PaymentWebhookException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public static function invalidPayload(string $reason = ''): self
    {
        return new self('Payload de webhook invalide. '.$reason, 400);
    }

    public static function invalidSignature(): self
    {
        return new self('Signature de webhook invalide — événement rejeté (protection anti-fraude).', 400);
    }

    public static function transactionNotApprovedByApi(string $transactionId, string $actualStatus): self
    {
        return new self(
            "La vérification auprès de l'API du PSP indique que la transaction [{$transactionId}] n'est pas approuvée (statut réel: {$actualStatus}).",
            409
        );
    }

    public static function orderNotFound(string $reference): self
    {
        return new self("Aucune commande ne correspond à la référence de transaction [{$reference}].", 404);
    }
}
