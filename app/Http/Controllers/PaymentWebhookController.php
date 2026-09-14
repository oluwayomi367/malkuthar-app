<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentWebhookException;
use App\Services\Payments\ProcessPaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * PaymentWebhookController — point d'entrée unique des notifications
 * serveur-à-serveur des PSP (§22, §26, §66) : POST /webhooks/{provider}.
 *
 * Contrôleur volontairement fin : authentification de la charge utile,
 * re-vérification via l'API du PSP, mise à jour de la commande,
 * distribution des commissions et livraison digitale vivent dans les
 * Services. La route est exclue de la vérification CSRF (bootstrap/app.php)
 * et ne doit jamais être protégée par `auth`.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(private readonly ProcessPaymentWebhook $processor) {}

    public function __invoke(Request $request, string $provider): Response
    {
        try {
            $this->processor->handle($provider, $request->getContent(), $request->headers->all());
        } catch (PaymentWebhookException $e) {
            report($e);

            return response($e->getMessage(), $e->statusCode());
        } catch (Throwable $e) {
            report($e);

            return response('Erreur interne lors du traitement du webhook.', 500);
        }

        // Réponse 2xx rapide attendue par les PSP pour éviter les retries.
        return response('OK', 200);
    }
}
