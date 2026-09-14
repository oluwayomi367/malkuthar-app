<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Payments\PaymentSession;
use App\Payments\PaymentVerification;

/**
 * Double de test générique pour PaymentGateway : évite tout appel réseau
 * réel dans les tests qui ne portent pas spécifiquement sur l'intégration
 * PSP (ex: checkout, affiliation).
 */
class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly string $providerName) {}

    public function provider(): string
    {
        return $this->providerName;
    }

    public function createCheckoutSession(Order $order, array $customer): PaymentSession
    {
        return new PaymentSession(
            provider: $this->providerName,
            reference: 'FAKE-'.$order->order_number,
            paymentUrl: 'https://pay.example.test/'.$order->order_number,
        );
    }

    public function verifyTransaction(string $reference): PaymentVerification
    {
        return new PaymentVerification(status: PaymentVerification::PENDING, reference: $reference);
    }

    public function parseWebhook(string $rawPayload, array $headers): ?string
    {
        return null;
    }
}
