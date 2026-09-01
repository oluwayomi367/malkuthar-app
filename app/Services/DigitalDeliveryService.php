<?php

namespace App\Services;

use App\Mail\DigitalProductsDelivered;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * DigitalDeliveryService — livraison automatique des produits digitaux
 * après confirmation définitive du paiement (§9 du cahier des charges).
 *
 * Flux attendu (§9) :
 *   Paiement confirmé -> Commande validée -> Accès au produit ->
 *   Email de confirmation -> Téléchargement
 *
 * NOTE IMPORTANTE : la génération de liens signés temporaires ci-dessous
 * suppose l'existence d'une route nommée `products.download` (contrôleur
 * de téléchargement sécurisé, avec vérification des autorisations et
 * limitation du nombre de téléchargements — §9). Cette route n'a pas
 * encore été développée dans les phases précédentes ; c'est la prochaine
 * brique à construire pour que ce service soit pleinement fonctionnel.
 */
class DigitalDeliveryService
{
    private const LINK_VALIDITY_DAYS = 7;

    public function deliverForOrder(Order $order): void
    {
        $digitalItems = $order->items->filter(
            fn ($item) => $item->product_type === 'digital'
        );

        if ($digitalItems->isEmpty()) {
            return;
        }

        $recipientEmail = $order->user?->email;

        if (! $recipientEmail) {
            // Cas non couvert par le schéma actuel : le checkout invité
            // (sans compte) ne persiste pas encore d'email sur la
            // commande elle-même. À corriger en ajoutant une colonne
            // `guest_email` sur `orders` si le parcours invité doit être
            // supporté pour les achats digitaux.
            Log::warning('DigitalDeliveryService: aucun email destinataire disponible, livraison impossible.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $downloadLinks = $this->generateSecureDownloadLinks($digitalItems);

        Mail::to($recipientEmail)->queue(
            new DigitalProductsDelivered($order, $downloadLinks)
        );

        Log::info('DigitalDeliveryService: livraison digitale envoyée.', [
            'order_id' => $order->id,
            'recipient' => $recipientEmail,
            'items_count' => $digitalItems->count(),
        ]);
    }

    /**
     * Génère un lien de téléchargement signé et temporaire par article
     * digital (§9 - liens sécurisés, liens temporaires).
     *
     * @return Collection<string, string> nom du produit => URL signée
     */
    private function generateSecureDownloadLinks(Collection $digitalItems): Collection
    {
        return $digitalItems->mapWithKeys(function ($item) {
            $url = URL::temporarySignedRoute(
                'products.download',
                now()->addDays(self::LINK_VALIDITY_DAYS),
                ['orderItem' => $item->id]
            );

            return [$item->product_name => $url];
        });
    }
}
