<?php

namespace App\Mail;

use App\Models\ReportSubscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ScheduledReport extends Mailable
{
    public function __construct(
        public ReportSubscription $subscription,
        public string $excelData,
        public string $filename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->subscription->reportTypeLabel()} — {$this->subscription->intervalLabel()} Report",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.scheduled-report',
            with: [
                'userName' => $this->subscription->user->name,
                'reportName' => $this->subscription->reportTypeLabel(),
                'interval' => $this->subscription->intervalLabel(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->excelData, $this->filename)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
