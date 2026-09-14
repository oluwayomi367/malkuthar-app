<?php

namespace App\Payments;

/**
 * État réel et certifié d'une transaction, obtenu en ré-interrogeant
 * l'API du PSP (jamais le seul payload du webhook — §26).
 */
final class PaymentVerification
{
    public const PAID = 'paid';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public function __construct(
        public readonly string $status,
        public readonly string $reference,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly array $metadata = [],
        public readonly array $raw = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::REFUNDED;
    }
}
