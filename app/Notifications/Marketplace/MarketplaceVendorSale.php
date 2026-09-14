<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\MarketplaceSuborder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — nouvelle vente pour un vendeur (une sous-commande payée).
 */
class MarketplaceVendorSale extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MarketplaceSuborder $suborder) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $net = number_format((float) $this->suborder->vendor_net, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Nouvelle vente sur la place de marché')
            ->line("Vous avez une nouvelle commande ({$this->suborder->suborder_number}).")
            ->line("Montant net après commission : {$net}.")
            ->line('Il sera disponible au retrait après la période de validation.')
            ->action('Voir mes commandes', url('/vendeur/commandes'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.vendor.sale',
            'suborder_id' => $this->suborder->id,
            'suborder_number' => $this->suborder->suborder_number,
            'vendor_net' => (float) $this->suborder->vendor_net,
        ];
    }
}
