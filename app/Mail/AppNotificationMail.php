<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $mailTitle,
        public readonly string $mailBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.notification',
            with: [
                'title' => $this->mailTitle,
                'body' => $this->mailBody,
            ],
        );
    }
}
