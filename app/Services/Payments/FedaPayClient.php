<?php

namespace App\Services\Payments;

use FedaPay\FedaPay;
use FedaPay\Transaction as FedaPayTransaction;
use FedaPay\Webhook as FedaPayWebhook;

/**
 * Fine enveloppe autour du SDK FedaPay : isole les appels statiques du
 * SDK pour permettre de les simuler dans les tests (bind d'un double de
 * cette classe dans le conteneur).
 */
class FedaPayClient
{
    public function __construct()
    {
        FedaPay::setApiKey((string) config('services.fedapay.secret_key'));
        FedaPay::setEnvironment((string) config('services.fedapay.environment', 'sandbox'));
    }

    public function createTransaction(array $data): object
    {
        return FedaPayTransaction::create($data);
    }

    public function retrieveTransaction(int|string $id): object
    {
        return FedaPayTransaction::retrieve($id);
    }

    /**
     * @return object avec au moins ->token et ->url
     */
    public function generateToken(object $transaction): object
    {
        return $transaction->generateToken();
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): object
    {
        return FedaPayWebhook::constructEvent($payload, $signature, $secret);
    }
}
