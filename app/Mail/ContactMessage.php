<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

/**
 * Message de contact envoyé à MALKUTHAR ; répond à l'adresse du visiteur.
 */
class ContactMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly string $subjectLabel,
        public readonly string $body,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Contact — {$this->subjectLabel}")
            ->replyTo(new Address($this->senderEmail, $this->senderName))
            ->view('emails.contact');
    }
}
