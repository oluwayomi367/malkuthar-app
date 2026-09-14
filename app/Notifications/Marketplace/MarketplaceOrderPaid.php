<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\MarketplaceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — confirmation de commande payée, adressée à l'acheteur.
 */
class MarketplaceOrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MarketplaceOrder $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = number_format((float) $this->order->grand_total, 0, ',', ' ');

        return (new MailMessage)
            ->subject("Commande {$this->order->order_number} confirmée")
            ->line("Votre commande {$this->order->order_number} d'un montant de {$total} {$this->order->currency} est confirmée.")
            ->line("Elle regroupe {$this->order->suborders->count()} vendeur(s) qui préparent vos articles.");
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.order.paid',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
