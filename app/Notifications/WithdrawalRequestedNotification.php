<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * §59 — "Votre demande de retrait de 25 000 FCFA a été enregistrée."
 */
class WithdrawalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Withdrawal $withdrawal) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->withdrawal->amount, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Demande de retrait enregistrée — MALKUTHAR')
            ->line("Votre demande de retrait de {$amount} {$this->withdrawal->currency} a été enregistrée.")
            ->line("Référence : {$this->withdrawal->reference}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'withdrawal.requested',
            'withdrawal_id' => $this->withdrawal->id,
            'reference' => $this->withdrawal->reference,
            'amount' => (float) $this->withdrawal->amount,
            'currency' => $this->withdrawal->currency,
            'message' => sprintf(
                'Votre demande de retrait de %s %s a été enregistrée.',
                number_format((float) $this->withdrawal->amount, 0, ',', ' '),
                $this->withdrawal->currency
            ),
        ];
    }
}
