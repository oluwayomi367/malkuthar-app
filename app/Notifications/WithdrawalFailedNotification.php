<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * §59 — "Votre retrait n'a pas pu être effectué. Consultez votre espace
 * affilié." Les fonds sont recrédités (§10 Test 10), le message doit
 * rassurer sans détailler la cause technique.
 */
class WithdrawalFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Withdrawal $withdrawal) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Retrait non abouti — MALKUTHAR')
            ->line("Votre retrait n'a pas pu être effectué. Consultez votre espace affilié.")
            ->line("Référence : {$this->withdrawal->reference}")
            ->line('Les fonds ont été recrédités sur votre solde disponible.')
            ->action('Voir mon espace affilié', url('/affiliate/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'withdrawal.failed',
            'withdrawal_id' => $this->withdrawal->id,
            'reference' => $this->withdrawal->reference,
            'message' => "Votre retrait n'a pas pu être effectué. Consultez votre espace affilié.",
        ];
    }
}
