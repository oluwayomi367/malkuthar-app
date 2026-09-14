<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * §22 — Résout le PaymentGateway concret pour un PSP donné. Ajouter un
 * prestataire = une entrée ici + une classe implémentant PaymentGateway.
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private array $gateways = [
        'fedapay' => FedaPayGateway::class,
        'cinetpay' => CinetPayGateway::class,
    ];

    public function __construct(private readonly Container $app) {}

    public function for(string $provider): PaymentGateway
    {
        $provider = strtolower(trim($provider));

        if (! isset($this->gateways[$provider])) {
            throw new InvalidArgumentException("Prestataire de paiement non supporté : {$provider}");
        }

        return $this->app->make($this->gateways[$provider]);
    }

    public function supports(string $provider): bool
    {
        return isset($this->gateways[strtolower(trim($provider))]);
    }

    /** @return list<string> */
    public function available(): array
    {
        return array_keys($this->gateways);
    }
}
