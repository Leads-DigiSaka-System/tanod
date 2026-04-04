<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $scheduledDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Deletion Request — TanodTractor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-deletion-requested',
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
