<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Payments\PaymentSession;

/**
 * §26 — Initie le paiement d'une commande auprès du PSP choisi et
 * enregistre sur la commande la référence de transaction qui servira à
 * la réconciliation via webhook.
 */
class PaymentInitiationService
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    /**
     * @param  array{email?: string, first_name?: string, last_name?: string, phone?: string}  $customer
     */
    public function initiate(Order $order, array $customer): PaymentSession
    {
        $gateway = $this->gateways->for($order->payment_provider);

        $session = $gateway->createCheckoutSession($order, $customer);

        $order->update([
            'payment_provider' => $session->provider,
            'payment_reference' => $session->reference,
        ]);

        return $session;
    }
}
