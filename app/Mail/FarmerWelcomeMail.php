<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FarmerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $farmerName,
        public string $fcaName,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Maligayang Pagdating sa TanodTractor!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer-welcome',
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
