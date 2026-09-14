<?php

namespace Tests;

use App\Services\Payments\CinetPayGateway;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakePaymentGateway;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Par défaut, aucun test ne doit joindre un vrai PSP par le
        // réseau. Les tests qui portent spécifiquement sur l'intégration
        // FedaPay/CinetPay rebindent leur propre double (voir
        // tests/Feature/Payments/*).
        $this->app->bind(FedaPayGateway::class, fn () => new FakePaymentGateway('fedapay'));
        $this->app->bind(CinetPayGateway::class, fn () => new FakePaymentGateway('cinetpay'));
    }
}
