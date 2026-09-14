<?php

namespace App\Services\Payments;

use App\Contracts\PayoutGateway;
use App\Models\Withdrawal;
use Illuminate\Contracts\Container\Container;

/**
 * §54 — Résout le premier PayoutGateway qui sait traiter un retrait
 * donné. Le ManualPayoutGateway, toujours en dernier, garantit qu'un
 * retrait n'est jamais bloqué faute de prestataire automatisé.
 */
class PayoutGatewayManager
{
    /** @var array<int, class-string<PayoutGateway>> */
    private array $gateways = [
        // Ajouter ici un futur prestataire automatisé (ex: FedaPayPayoutGateway)
        // AVANT ManualPayoutGateway pour qu'il soit essayé en priorité.
        ManualPayoutGateway::class,
    ];

    public function __construct(private readonly Container $app) {}

    public function for(Withdrawal $withdrawal): PayoutGateway
    {
        foreach ($this->gateways as $class) {
            /** @var PayoutGateway $gateway */
            $gateway = $this->app->make($class);

            if ($gateway->supports($withdrawal->method, $withdrawal->country)) {
                return $gateway;
            }
        }

        return $this->app->make(ManualPayoutGateway::class);
    }
}
