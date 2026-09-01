<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * DigitalProductsDelivered — email envoyé après confirmation du paiement
 * pour un panier contenant au moins un produit digital (§9, §59).
 *
 * NOTE : gabarit HTML minimal auto-contenu (pas de vue Blade dédiée) pour
 * rester simple à cette étape. À remplacer par un template Markdown/Blade
 * avec la charte graphique MALKUTHAR avant mise en production.
 */
class DigitalProductsDelivered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<string, string>  $downloadLinks  nom du produit => URL signée
     */
    public function __construct(
        public readonly Order $order,
        public readonly Collection $downloadLinks,
    ) {
    }

    public function build(): self
    {
        $items = $this->downloadLinks
            ->map(function (string $url, string $productName) {
                $safeName = e($productName);

                return "<li><strong>{$safeName}</strong> — <a href=\"{$url}\">Télécharger</a> (lien valable ".DigitalProductsDelivered::linkValidityLabel().")</li>";
            })
            ->implode('');

        $orderNumber = e($this->order->order_number);

        $html = <<<HTML
            <p>Bonjour,</p>
            <p>Votre paiement pour la commande <strong>{$orderNumber}</strong> a bien été confirmé. Voici vos produits :</p>
            <ul>{$items}</ul>
            <p>Ces liens sont personnels et à usage limité, merci de ne pas les partager.</p>
            <p>— L'équipe MALKUTHAR</p>
        HTML;

        return $this->subject('Vos produits MALKUTHAR sont disponibles')->html($html);
    }

    private static function linkValidityLabel(): string
    {
        return '7 jours';
    }
}
