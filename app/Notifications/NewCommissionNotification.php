<?php

namespace App\Notifications;

use App\Models\LedgerEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * §59 — "Vous venez de générer une commission de 1 500 FCFA."
 */
class NewCommissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LedgerEntry $entry) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->entry->amount, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Nouvelle commission MALKUTHAR')
            ->line("Vous venez de générer une commission de {$amount} {$this->entry->currency}.")
            ->line("Niveau {$this->entry->level} — {$this->entry->description}")
            ->line('Elle apparaîtra en « en attente » jusqu\'à sa validation.')
            ->action('Voir mon espace affilié', url('/affiliate/dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'commission.new',
            'ledger_entry_id' => $this->entry->id,
            'level' => $this->entry->level,
            'amount' => (float) $this->entry->amount,
            'currency' => $this->entry->currency,
            'message' => sprintf(
                'Vous venez de générer une commission de %s %s.',
                number_format((float) $this->entry->amount, 0, ',', ' '),
                $this->entry->currency
            ),
        ];
    }
}
