<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Payments\PaymentSession;
use App\Payments\PaymentVerification;
use Throwable;

/**
 * §22 — Implémentation FedaPay du contrat PaymentGateway.
 */
class FedaPayGateway implements PaymentGateway
{
    public function __construct(private readonly FedaPayClient $client) {}

    public function provider(): string
    {
        return 'fedapay';
    }

    public function createCheckoutSession(Order $order, array $customer): PaymentSession
    {
        // §26 — montant FedaPay en unité entière de la devise (XOF sans
        // centimes). `custom_metadata` est le champ officiel repris tel
        // quel dans le webhook / la vérification (nous y rangeons l'id de
        // commande pour la réconciliation).
        $transaction = $this->client->createTransaction([
            'description' => 'Commande '.$order->order_number,
            'amount' => (int) round((float) $order->total_amount),
            'currency' => ['iso' => $order->currency],
            'callback_url' => route('checkout.return', ['order' => $order->id]),
            'customer' => array_filter([
                'firstname' => $customer['first_name'] ?? null,
                'lastname' => $customer['last_name'] ?? null,
                'email' => $customer['email'] ?? null,
            ]),
            'custom_metadata' => ['order_id' => $order->id],
        ]);

        $token = $this->client->generateToken($transaction);

        return new PaymentSession(
            provider: $this->provider(),
            reference: (string) ($transaction->id ?? ''),
            paymentUrl: (string) ($token->url ?? ''),
            raw: ['token' => $token->token ?? null],
        );
    }

    public function verifyTransaction(string $reference): PaymentVerification
    {
        $tx = $this->client->retrieveTransaction($reference);

        $status = match ((string) ($tx->status ?? '')) {
            'approved', 'transferred' => PaymentVerification::PAID,
            'refunded' => PaymentVerification::REFUNDED,
            'declined', 'canceled', 'failed' => PaymentVerification::FAILED,
            default => PaymentVerification::PENDING,
        };

        $currency = $tx->currency ?? null;

        return new PaymentVerification(
            status: $status,
            reference: (string) ($tx->id ?? $reference),
            amount: isset($tx->amount) ? (float) $tx->amount : null,
            currency: is_array($currency) ? ($currency['iso'] ?? null) : (is_object($currency) ? ($currency->iso ?? null) : null),
            metadata: $this->normalizeMetadata($tx->custom_metadata ?? $tx->metadata ?? null),
            raw: (array) $tx,
        );
    }

    /**
     * `custom_metadata` peut revenir en tableau, en objet SDK ou en JSON.
     *
     * @return array<string, mixed>
     */
    private function normalizeMetadata(mixed $meta): array
    {
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($meta) && method_exists($meta, 'toArray')) {
            return $meta->toArray();
        }

        return (array) ($meta ?? []);
    }

    public function parseWebhook(string $rawPayload, array $headers): ?string
    {
        $signature = $headers['x-fedapay-signature'][0]
            ?? $headers['X-FEDAPAY-SIGNATURE'][0]
            ?? null;

        if (! $signature) {
            return null;
        }

        try {
            $event = $this->client->constructWebhookEvent(
                $rawPayload,
                $signature,
                (string) config('services.fedapay.webhook_secret'),
            );
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return isset($event->entity->id) ? (string) $event->entity->id : null;
    }
}
