<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\VendorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — le compte vendeur est validé (ou réactivé).
 */
class VendorApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly VendorProfile $profile) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre espace vendeur MALKUTHAR est ouvert')
            ->greeting('Bonne nouvelle !')
            ->line("Votre espace vendeur « {$this->profile->display_name} » est actif.")
            ->line('Vous pouvez maintenant créer vos offres. Chacune sera relue par notre équipe avant sa mise en ligne.')
            ->action('Ouvrir mon espace vendeur', url('/vendeur'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.vendor.approved',
            'vendor_profile_id' => $this->profile->id,
            'message' => "Votre espace vendeur « {$this->profile->display_name} » est actif.",
        ];
    }
}
