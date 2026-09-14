<?php

namespace Tests\Support;

use App\Services\Payments\FedaPayClient;
use Throwable;

/**
 * Double de test pour FedaPayClient : simule le SDK FedaPay sans appel
 * réseau. `$transactions` est indexé par id et mutable entre les étapes
 * d'un scénario (ex: passer une transaction à "approved" puis "refunded").
 */
class FakeFedaPayClient extends FedaPayClient
{
    /** @var array<int, object> */
    public static array $transactions = [];

    public static ?Throwable $webhookException = null;

    private static int $nextId = 5000;

    public function createTransaction(array $data): object
    {
        $id = ++self::$nextId;
        $tx = (object) array_merge($data, ['id' => $id, 'status' => 'pending']);
        self::$transactions[$id] = $tx;

        return $tx;
    }

    public function retrieveTransaction(int|string $id): object
    {
        return self::$transactions[(int) $id] ?? (object) ['id' => (int) $id, 'status' => 'pending'];
    }

    public function generateToken(object $transaction): object
    {
        return (object) [
            'token' => 'tok_'.$transaction->id,
            'url' => 'https://sandbox-checkout.fedapay.com/'.$transaction->id,
        ];
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): object
    {
        if (self::$webhookException) {
            throw self::$webhookException;
        }

        $data = json_decode($payload, true) ?? [];

        return (object) [
            'name' => $data['name'] ?? 'transaction.approved',
            'entity' => (object) ['id' => $data['entity']['id'] ?? null],
        ];
    }

    public static function reset(): void
    {
        self::$transactions = [];
        self::$webhookException = null;
        self::$nextId = 5000;
    }

    /**
     * Enregistre/écrase une transaction simulée (contrôle direct du
     * statut vu par verifyTransaction, sans passer par createTransaction).
     */
    public static function seed(int $id, array $attributes): void
    {
        self::$transactions[$id] = (object) array_merge(['id' => $id], $attributes);
    }
}
