<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentWebhookException;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Services\AffiliationService;
use App\Services\DigitalDeliveryService;
use FedaPay\Error\SignatureVerification;
use FedaPay\Fedapay;
use FedaPay\Transaction as FedaPayTransaction;
use FedaPay\Webhook as FedaPayWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

/**
 * FedaPayWebhookService — traite les événements webhook envoyés par
 * FedaPay (§22, §26, §66 du cahier des charges).
 *
 * DOUBLE VÉRIFICATION ANTI-FRAUDE (§26) :
 *   1. Signature HMAC de l'en-tête X-FEDAPAY-SIGNATURE (protège contre un
 *      événement forgé par un tiers qui ne connaît pas la clé secrète du
 *      endpoint webhook).
 *   2. Re-vérification du statut RÉEL de la transaction en interrogeant
 *      directement l'API FedaPay avec notre clé secrète (protège contre
 *      un payload de webhook altéré/rejoué — FedaPay recommande
 *      explicitement de ne jamais se fier au seul contenu reçu et de
 *      toujours renvoyer une requête à l'API pour la valeur réelle).
 *
 * Le corps de la méthode métier est entièrement encapsulé dans
 * DB::transaction() (règle 2 de regles_laravel.txt).
 */
class FedaPayWebhookService
{
    private const HANDLED_EVENT = 'transaction.approved';

    public function __construct(
        private readonly AffiliationService $affiliationService,
        private readonly DigitalDeliveryService $digitalDeliveryService,
    ) {
        $this->configureSdk();
    }

    /**
     * Traite un webhook FedaPay brut (payload + en-tête de signature).
     *
     * @throws PaymentWebhookException en cas de payload/signature invalide,
     *                                  de transaction non confirmée par
     *                                  l'API, ou de commande introuvable.
     */
    public function handle(string $rawPayload, ?string $signatureHeader): void
    {
        $event = $this->verifySignatureAndParseEvent($rawPayload, $signatureHeader);

        // On ignore silencieusement les événements qui ne concernent pas
        // une transaction approuvée (transaction.created, .canceled, ...).
        // FedaPay attend un 2xx rapide même pour les événements non traités.
        if ($event->name !== self::HANDLED_EVENT) {
            Log::info('FedaPayWebhookService: événement ignoré (non géré).', [
                'event_name' => $event->name ?? 'unknown',
            ]);

            return;
        }

        $transactionId = $event->entity->id ?? null;

        if (! $transactionId) {
            throw PaymentWebhookException::invalidPayload("Identifiant de transaction manquant dans l'événement.");
        }

        // --- Étape 2 : re-vérification de l'authenticité auprès de l'API FedaPay ---
        // On ne fait JAMAIS confiance au statut contenu dans le payload du
        // webhook (potentiellement rejoué ou altéré) : on interroge
        // directement l'API FedaPay avec notre clé secrète pour obtenir le
        // statut réel et certifié de la transaction (§26).
        $transaction = $this->retrieveAuthoritativeTransaction($transactionId);

        if ($transaction->status !== 'approved') {
            throw PaymentWebhookException::transactionNotApprovedByApi($transactionId, (string) $transaction->status);
        }

        $order = $this->resolveOrderForTransaction($transaction);

        $wasJustProcessed = $this->markOrderAsPaidAndDistributeCommissions($order, $transaction);

        if ($wasJustProcessed) {
            // Effet de bord (email, génération de liens de téléchargement)
            // volontairement déclenché APRÈS le commit de la transaction
            // DB, pour ne jamais faire dépendre l'intégrité financière
            // d'un envoi d'email.
            $this->digitalDeliveryService->deliverForOrder($order->fresh('items'));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Étape 1 : vérification de la signature
    |--------------------------------------------------------------------------
    */

    private function verifySignatureAndParseEvent(string $rawPayload, ?string $signatureHeader)
    {
        if (! $signatureHeader) {
            throw PaymentWebhookException::invalidSignature();
        }

        $webhookSecret = (string) config('services.fedapay.webhook_secret');

        try {
            return FedaPayWebhook::constructEvent($rawPayload, $signatureHeader, $webhookSecret);
        } catch (UnexpectedValueException $e) {
            // Payload JSON malformé
            report($e);
            throw PaymentWebhookException::invalidPayload($e->getMessage());
        } catch (SignatureVerification $e) {
            // Signature invalide : l'événement ne provient pas de FedaPay,
            // ou a été altéré en transit -> rejet ferme (anti-fraude).
            report($e);
            throw PaymentWebhookException::invalidSignature();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Étape 2 : re-vérification via l'API FedaPay
    |--------------------------------------------------------------------------
    */

    private function retrieveAuthoritativeTransaction(int|string $transactionId): FedaPayTransaction
    {
        try {
            /** @var FedaPayTransaction $transaction */
            $transaction = FedaPayTransaction::retrieve($transactionId);
        } catch (Throwable $e) {
            report($e);
            throw PaymentWebhookException::invalidPayload(
                "Impossible de récupérer la transaction [{$transactionId}] auprès de l'API FedaPay: {$e->getMessage()}"
            );
        }

        return $transaction;
    }

    /*
    |--------------------------------------------------------------------------
    | Résolution de la commande
    |--------------------------------------------------------------------------
    */

    /**
     * Retrouve la commande MALKUTHAR correspondant à la transaction FedaPay.
     *
     * On cherche d'abord dans les métadonnées personnalisées transmises à
     * la création de la transaction (order_id), puis on retombe sur la
     * correspondance directe payment_reference = id transaction (renseigné
     * lors de l'initiation du paiement, Phase 3 - PaymentInitiationService).
     */
    private function resolveOrderForTransaction(FedaPayTransaction $transaction): Order
    {
        $metadata = (array) ($transaction->custom_metadata ?? $transaction->metadata ?? []);
        $orderId = $metadata['order_id'] ?? null;

        $order = $orderId
            ? Order::query()->find($orderId)
            : null;

        $order ??= Order::query()
            ->where('payment_reference', (string) $transaction->id)
            ->first();

        if (! $order) {
            throw PaymentWebhookException::orderNotFound((string) $transaction->id);
        }

        return $order;
    }

    /*
    |--------------------------------------------------------------------------
    | Traitement transactionnel
    |--------------------------------------------------------------------------
    */

    /**
     * Marque la commande comme payée et distribue les commissions, le tout
     * dans une transaction unique. Idempotent : si la commande est déjà
     * payée (webhook reçu deux fois, §27 - Test 7), ne fait rien et
     * retourne false.
     *
     * @return bool true si la commande vient réellement d'être traitée
     *              (premier événement reçu), false si c'était un doublon.
     */
    private function markOrderAsPaidAndDistributeCommissions(Order $order, FedaPayTransaction $transaction): bool
    {
        return DB::transaction(function () use ($order, $transaction) {
            /** @var Order $order */
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // --- Protection contre les doubles paiements (§27, Test 7) ---
            if ($order->payment_status === 'paid') {
                Log::info('FedaPayWebhookService: commande déjà payée, événement ignoré (idempotence).', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                ]);

                return false;
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_provider' => 'fedapay',
                'payment_reference' => (string) $transaction->id,
            ]);

            // Sécurité supplémentaire au niveau du ledger : même si un
            // appel concurrent avait franchi le verrou d'une façon
            // inattendue, la contrainte unique sur `reference` dans
            // AffiliationService empêche toute double écriture de
            // commission (défense en profondeur).
            $alreadyHasCommissions = LedgerEntry::query()
                ->where('order_id', $order->id)
                ->where('source_type', 'commission')
                ->exists();

            if (! $alreadyHasCommissions) {
                $this->affiliationService->distributeCommissions($order);
            }

            Log::info('FedaPayWebhookService: paiement confirmé et traité.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transaction_id' => $transaction->id,
            ]);

            return true;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration du SDK
    |--------------------------------------------------------------------------
    */

    private function configureSdk(): void
    {
        Fedapay::setApiKey((string) config('services.fedapay.secret_key'));
        Fedapay::setEnvironment((string) config('services.fedapay.environment', 'sandbox'));
    }
}
