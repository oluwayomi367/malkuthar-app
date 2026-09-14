<?php

namespace App\Notifications\Marketplace;

use App\Models\Marketplace\VendorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MARKETPLACE — la candidature vendeur n'est pas retenue.
 */
class VendorApplicationRejected extends Notification implements ShouldQueue
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
            ->subject('Votre candidature vendeur MALKUTHAR')
            ->line("Nous ne pouvons pas donner suite à votre demande d'espace vendeur pour le moment.")
            ->line("Motif : {$this->reason}")
            ->line('Vous pouvez nous recontacter si votre situation évolue.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace.vendor.rejected',
            'vendor_profile_id' => $this->profile->id,
            'reason' => $this->reason,
            'message' => "Votre candidature vendeur n'a pas été retenue.",
        ];
    }
}
