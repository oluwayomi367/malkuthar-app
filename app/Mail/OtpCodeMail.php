<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * §55-§56 — envoi du code de vérification à usage unique.
 */
class OtpCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purposeLabel,
    ) {}

    public function build(): self
    {
        return $this->subject('Votre code de vérification MALKUTHAR')->html(
            "<p>Votre code de vérification pour « {$this->purposeLabel} » est :</p>".
            "<p style=\"font-size:24px;font-weight:700;letter-spacing:4px;\">{$this->code}</p>".
            '<p>Ce code expire dans 10 minutes. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>'
        );
    }
}
