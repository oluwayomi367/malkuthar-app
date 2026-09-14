<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * §59 — "Votre retrait de 25 000 FCFA a été effectué."
 */
class WithdrawalPaidNotification extends Notification implements ShouldQueue
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
            ->subject('Retrait effectué — MALKUTHAR')
            ->line("Votre retrait de {$amount} {$this->withdrawal->currency} a été effectué.")
            ->line("Référence : {$this->withdrawal->reference}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'withdrawal.paid',
            'withdrawal_id' => $this->withdrawal->id,
            'reference' => $this->withdrawal->reference,
            'amount' => (float) $this->withdrawal->amount,
            'currency' => $this->withdrawal->currency,
            'message' => sprintf(
                'Votre retrait de %s %s a été effectué.',
                number_format((float) $this->withdrawal->amount, 0, ',', ' '),
                $this->withdrawal->currency
            ),
        ];
    }
}
