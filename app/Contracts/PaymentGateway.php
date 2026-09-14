<?php

namespace App\Contracts;

use App\Models\Order;
use App\Payments\PaymentSession;
use App\Payments\PaymentVerification;

/**
 * §22 — Contrat commun à tous les prestataires de paiement (FedaPay,
 * CinetPay, futurs PSP). Ajouter un PSP = implémenter cette interface et
 * l'enregistrer dans PaymentGatewayManager, sans toucher au reste.
 */
interface PaymentGateway
{
    /** Identifiant court du PSP : 'fedapay', 'cinetpay', ... */
    public function provider(): string;

    /**
     * Crée la transaction côté PSP et renvoie l'URL de paiement.
     *
     * @param  array{email?: string, first_name?: string, last_name?: string, phone?: string}  $customer
     */
    public function createCheckoutSession(Order $order, array $customer): PaymentSession;

    /**
     * Ré-interroge l'API du PSP pour connaître le statut réel de la
     * transaction (§26). $reference est la référence stockée sur la
     * commande à l'initiation.
     */
    public function verifyTransaction(string $reference): PaymentVerification;

    /**
     * Vérifie l'authenticité d'un webhook (signature/token) et renvoie la
     * référence de transaction concernée, ou null si la charge utile est
     * invalide/non pertinente.
     *
     * @param  array<string, string>  $headers
     */
    public function parseWebhook(string $rawPayload, array $headers): ?string;
}
