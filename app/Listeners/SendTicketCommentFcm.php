<?php

namespace App\Listeners;

use App\Events\TicketCommentAdded;
use App\Models\User;
use App\Services\FcmService;

class SendTicketCommentFcm
{
    public function __construct(
        private FcmService $fcm,
    ) {}

    /**
     * Send FCM push to all ticket participants except the commenter.
     */
    public function handle(TicketCommentAdded $event): void
    {
        $comment = $event->comment;
        $ticket = $comment->ticket;

        $recipientIds = $event->recipientIds();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $recipients = User::whereIn('id', $recipientIds)
            ->whereNotNull('fcm_token')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $senderName = $comment->user->name ?? 'Someone';
        $body = $comment->body ?: ($comment->attachment_path ? 'Sent an attachment' : 'Sent a message');

        $this->fcm->sendToUsers(
            $recipients,
            "$senderName replied on a ticket",
            $body,
            [
                'type' => 'ticket_comment',
                'ticket_id' => (string) $ticket->id,
            ],
        );
    }
}
