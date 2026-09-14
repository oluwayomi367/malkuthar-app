<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * §19 — notifications de rendez-vous : confirmation immédiate, rappel avant
 * le rendez-vous, notification de modification et notification d'annulation.
 *
 * Canaux : email + notification interne (base). Le SMS (`vonage`) et
 * WhatsApp seront ajoutés dans via() dès que l'intégration sera disponible
 * (§19) — le même principe est déjà en place côté affiliation.
 */
class ServiceBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const CONFIRMED = 'confirmed';

    public const REMINDER = 'reminder';

    public const RESCHEDULED = 'rescheduled';

    public const CANCELLED = 'cancelled';

    public function __construct(
        public readonly OrderItem $item,
        public readonly string $type,
        public readonly ?Carbon $previousDatetime = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // TODO §19 : ajouter 'vonage' (SMS) et un canal 'whatsapp' quand
        // l'intégration sera disponible. Le reste du code n'a pas à changer.
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = $this->item->product_name;
        $when = $this->item->reservation_datetime?->translatedFormat('l d F Y à H:i');
        $mail = (new MailMessage)->greeting('Bonjour,');

        $mail = match ($this->type) {
            self::CONFIRMED => $mail
                ->subject("Confirmation de votre rendez-vous — {$service}")
                ->line("Votre rendez-vous pour « {$service} » est confirmé.")
                ->line("Date : {$when}."),
            self::REMINDER => $mail
                ->subject("Rappel : votre rendez-vous {$service}")
                ->line("Votre rendez-vous pour « {$service} » approche.")
                ->line("Date : {$when}."),
            self::RESCHEDULED => $mail
                ->subject("Votre rendez-vous {$service} a été modifié")
                ->line("La date de votre rendez-vous pour « {$service} » a changé.")
                ->line('Ancienne date : '.$this->previousDatetime?->translatedFormat('l d F Y à H:i').'.')
                ->line("Nouvelle date : {$when}."),
            self::CANCELLED => $mail
                ->subject("Annulation de votre rendez-vous {$service}")
                ->line("Votre rendez-vous pour « {$service} » du {$when} a été annulé.")
                ->line('Pour reprogrammer, contactez-nous ou passez une nouvelle réservation.'),
            default => $mail->subject("Votre rendez-vous {$service}"),
        };

        if ($this->type !== self::CANCELLED) {
            if ($link = $this->item->product?->meeting_link) {
                $mail->line("Lien de visioconférence : {$link}");
            } elseif ($location = $this->item->product?->location) {
                $mail->line("Lieu : {$location}");
            }
        }

        return $mail->salutation("— L'équipe MALKUTHAR");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'service_booking',
            'event' => $this->type,
            'title' => match ($this->type) {
                self::CONFIRMED => 'Rendez-vous confirmé',
                self::REMINDER => 'Rappel de rendez-vous',
                self::RESCHEDULED => 'Rendez-vous modifié',
                self::CANCELLED => 'Rendez-vous annulé',
                default => 'Rendez-vous',
            },
            'service' => $this->item->product_name,
            'product_id' => $this->item->product_id,
            'order_id' => $this->item->order_id,
            'order_item_id' => $this->item->id,
            'reservation_at' => $this->item->reservation_datetime?->toIso8601String(),
            'previous_reservation_at' => $this->previousDatetime?->toIso8601String(),
            'meeting_link' => $this->item->product?->meeting_link,
            'location' => $this->item->product?->location,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
