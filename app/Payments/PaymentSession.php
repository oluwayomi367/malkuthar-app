<?php

namespace App\Payments;

/**
 * Résultat de l'initiation d'un paiement auprès d'un PSP : l'URL vers
 * laquelle rediriger le client et la référence de transaction côté PSP
 * (stockée sur la commande pour la réconciliation via webhook, §26).
 */
final class PaymentSession
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly string $paymentUrl,
        public readonly array $raw = [],
    ) {}
}
