<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentWebhookException;
use App\Models\Marketplace\MarketplaceOrder;
use App\Models\Order;
use App\Payments\PaymentVerification;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration générique du traitement d'un webhook PSP (§26, §27, §66),
 * indépendante du prestataire :
 *   1. le gateway authentifie la charge utile et en extrait la référence ;
 *   2. on ré-interroge l'API du PSP pour le statut réel ;
 *   3. on retrouve la commande ;
 *   4. on confirme le paiement / le remboursement (idempotent).
 */
class ProcessPaymentWebhook
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentConfirmationService $confirmation,
    ) {}

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    public function handle(string $provider, string $rawPayload, array $headers): void
    {
        if (! $this->gateways->supports($provider)) {
            throw PaymentWebhookException::invalidPayload("PSP inconnu : {$provider}");
        }

        $gateway = $this->gateways->for($provider);

        $reference = $gateway->parseWebhook($rawPayload, $headers);

        if (! $reference) {
            throw PaymentWebhookException::invalidSignature();
        }

        // §26 — statut réel, jamais celui du payload.
        $verification = $gateway->verifyTransaction($reference);

        // Route les références de la place de marché (préfixe MP-) vers le
        // service de confirmation dédié — pas d'affiliation, règlement vendeur.
        if ($this->isMarketplaceReference($verification, $reference)) {
            $this->handleMarketplace($verification, $reference);

            return;
        }

        $order = $this->resolveOrder($verification, $reference);

        if ($verification->isPaid()) {
            $this->confirmation->confirmPaid($order, $verification);

            return;
        }

        if ($verification->isRefunded()) {
            $this->confirmation->confirmRefunded($order, $verification);

            return;
        }

        // pending / failed : rien à faire, on acquitte simplement (200).
        Log::info('ProcessPaymentWebhook: statut non final, acquitté sans action.', [
            'provider' => $provider,
            'reference' => $reference,
            'status' => $verification->status,
        ]);
    }

    private function isMarketplaceReference(PaymentVerification $verification, string $reference): bool
    {
        $prefix = (string) config('marketplace.payment_reference_prefix', 'MP-');

        if (str_starts_with($reference, $prefix)) {
            return true;
        }

        $mpId = $verification->metadata['marketplace_order_id'] ?? null;

        return $mpId !== null && MarketplaceOrder::query()->whereKey($mpId)->exists();
    }

    private function handleMarketplace(PaymentVerification $verification, string $reference): void
    {
        $mpId = $verification->metadata['marketplace_order_id'] ?? null;

        $order = ($mpId ? MarketplaceOrder::find($mpId) : null)
            ?? MarketplaceOrder::query()->where('payment_reference', $reference)->first();

        if (! $order) {
            throw PaymentWebhookException::orderNotFound($reference);
        }

        if ($verification->isPaid()) {
            app(MarketplacePaymentConfirmationService::class)->confirmPaid($order, $reference);
        }

        // pending / failed / refunded : acquitté sans action pour l'instant.
    }

    private function resolveOrder(PaymentVerification $verification, string $reference): Order
    {
        $orderId = $verification->metadata['order_id'] ?? null;

        $order = $orderId ? Order::query()->find($orderId) : null;

        $order ??= Order::query()->where('payment_reference', $reference)->first();

        if (! $order) {
            throw PaymentWebhookException::orderNotFound($reference);
        }

        return $order;
    }
}
