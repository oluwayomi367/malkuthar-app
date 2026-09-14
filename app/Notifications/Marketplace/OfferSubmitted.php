<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — une offre vient d'être soumise : à destination des admins.
 */
class OfferSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MarketplaceOffer $offer) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Offre marketplace à valider')
            ->line("« {$this->offer->title} » ({$this->offer->type}) de {$this->offer->vendor->display_name} attend une revue.")
            ->action('Ouvrir la file de modération', url('/admin/marketplace/offers?status=pending_review'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.offer.submitted',
            'offer_id' => $this->offer->id,
            'message' => "« {$this->offer->title} » attend une revue.",
        ];
    }
}
