<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentWebhookException;
use App\Services\Payments\FedaPayWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * PaymentWebhookController — reçoit les notifications serveur-à-serveur
 * des PSP (FedaPay, CinetPay — §22, §66). Contrôleur intentionnellement
 * fin : toute la logique (vérification de signature, re-vérification via
 * l'API du PSP, mise à jour de la commande, distribution des commissions,
 * livraison digitale) vit dans les Services dédiés.
 *
 * IMPORTANT (configuration à faire au niveau du projet, hors de ce
 * fichier) :
 *  - Cette route doit être exclue de la vérification CSRF (FedaPay
 *    n'envoie pas de jeton Laravel) : à ajouter dans la configuration du
 *    middleware `ValidateCsrfToken` / `bootstrap/app.php`.
 *  - Elle ne doit PAS être protégée par un middleware `auth`.
 *  - Elle doit rester accessible en HTTPS uniquement.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly FedaPayWebhookService $fedaPayWebhookService,
    ) {
    }

    /**
     * POST /webhooks/fedapay
     *
     * Vérifie l'authenticité de l'événement (signature HMAC ET
     * re-vérification directe auprès de l'API FedaPay — §26), puis, si le
     * paiement est confirmé et qu'il s'agit du premier événement reçu
     * pour cette commande (§27), fait passer la commande au statut payée,
     * déclenche la livraison automatique des produits digitaux (§9) et
     * distribue les commissions d'affiliation (§28-§39).
     */
    public function handleFedaPayWebhook(Request $request): Response
    {
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header('X-FEDAPAY-SIGNATURE');

        try {
            $this->fedaPayWebhookService->handle($rawPayload, $signatureHeader);
        } catch (PaymentWebhookException $e) {
            // Erreur métier identifiée (signature invalide, transaction
            // non approuvée côté API, commande introuvable...) : on
            // journalise et on répond avec le code HTTP adapté, sans
            // exposer de détail sensible au tiers appelant.
            report($e);

            return response($e->getMessage(), $e->statusCode());
        } catch (Throwable $e) {
            report($e);

            return response('Erreur interne lors du traitement du webhook.', 500);
        }

        // FedaPay attend une réponse 2xx rapide pour ne pas déclencher de
        // nouvelle tentative.
        return response('OK', 200);
    }
}
