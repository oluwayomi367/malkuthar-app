<?php

namespace App\Mail;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * §19 — confirmation immédiate d'un rendez-vous, ou rappel avant celui-ci
 * ($isReminder = true). Gabarit HTML minimal auto-contenu (cf. NOTE dans
 * DigitalProductsDelivered).
 */
class ServiceBookingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OrderItem $item,
        public readonly bool $isReminder = false,
    ) {}

    public function build(): self
    {
        $serviceName = e($this->item->product_name);
        $when = $this->item->reservation_datetime?->translatedFormat('d/m/Y à H:i') ?? '';
        $meetingLink = $this->item->product?->meeting_link;
        $location = $this->item->product?->location;

        $intro = $this->isReminder
            ? 'Rappel : votre rendez-vous approche.'
            : 'Votre réservation est confirmée.';

        $details = "<p><strong>{$serviceName}</strong><br>Le {$when}</p>";

        if ($meetingLink) {
            $safeLink = e($meetingLink);
            $details .= "<p>Lien de visioconférence : <a href=\"{$safeLink}\">{$safeLink}</a></p>";
        } elseif ($location) {
            $details .= '<p>Lieu : '.e($location).'</p>';
        }

        $html = "<p>Bonjour,</p><p>{$intro}</p>{$details}<p>— L'équipe MALKUTHAR</p>";

        return $this->subject(
            $this->isReminder ? 'Rappel de votre rendez-vous MALKUTHAR' : 'Confirmation de votre réservation MALKUTHAR'
        )->html($html);
    }
}
