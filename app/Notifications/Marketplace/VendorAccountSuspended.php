<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\VendorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — l'espace vendeur est suspendu : offres masquées, versements
 * gelés. Aucune donnée financière n'est supprimée.
 */
class VendorAccountSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly VendorProfile $profile,
        public readonly string $reason,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre espace vendeur MALKUTHAR est suspendu')
            ->line("Votre espace vendeur « {$this->profile->display_name} » a été suspendu.")
            ->line("Motif : {$this->reason}")
            ->line('Vos offres ne sont plus visibles et les versements sont en pause. Aucune donnée financière n\'est supprimée.')
            ->action('Contacter l\'équipe', url('/contact'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.vendor.suspended',
            'vendor_profile_id' => $this->profile->id,
            'reason' => $this->reason,
            'message' => "Votre espace vendeur « {$this->profile->display_name} » a été suspendu.",
        ];
    }
}
