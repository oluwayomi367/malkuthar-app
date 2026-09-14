<?php

namespace App\Notifications;

use App\Models\LedgerEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * §59 — "Votre commission de 1 500 FCFA est maintenant disponible."
 */
class CommissionAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LedgerEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->entry->amount, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Commission disponible — MALKUTHAR')
            ->line("Votre commission de {$amount} {$this->entry->currency} est maintenant disponible.")
            ->action('Retirer mes gains', url('/affiliate/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'commission.available',
            'ledger_entry_id' => $this->entry->id,
            'amount' => (float) $this->entry->amount,
            'currency' => $this->entry->currency,
            'message' => sprintf(
                'Votre commission de %s %s est maintenant disponible.',
                number_format((float) $this->entry->amount, 0, ',', ' '),
                $this->entry->currency
            ),
        ];
    }
}
