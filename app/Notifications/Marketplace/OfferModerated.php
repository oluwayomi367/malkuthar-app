<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — retour de modération au vendeur (approuvée / refusée /
 * corrections demandées / suspendue).
 */
class OfferModerated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MarketplaceOffer $offer,
        public readonly string $decision,
        public readonly ?string $note = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = [
            'approved' => 'approuvée et mise en ligne',
            'rejected' => 'refusée',
            'changes_requested' => 'à corriger',
            'suspended' => 'suspendue',
        ][$this->decision] ?? $this->decision;

        $mail = (new MailMessage)
            ->subject("Votre offre « {$this->offer->title} » — {$label}")
            ->line("Votre offre « {$this->offer->title} » est {$label}.");

        if ($this->note) {
            $mail->line("Message de l'équipe : {$this->note}");
        }

        return $mail->action('Voir mes offres', url('/vendeur/offres'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.offer.moderated',
            'offer_id' => $this->offer->id,
            'decision' => $this->decision,
            'note' => $this->note,
            'message' => "Votre offre « {$this->offer->title} » : {$this->decision}.",
        ];
    }
}
