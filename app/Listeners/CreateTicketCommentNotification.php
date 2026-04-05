<?php

namespace App\Listeners;

use App\Events\TicketCommentAdded;
use App\Models\Notification;

class CreateTicketCommentNotification
{
    /**
     * Create in-app Notification records for all ticket participants.
     */
    public function handle(TicketCommentAdded $event): void
    {
        $comment = $event->comment;
        $ticket = $comment->ticket;
        $senderName = $comment->user->name ?? 'Someone';
        $body = $comment->body ?: ($comment->attachment_path ? 'Sent an attachment' : 'Sent a message');

        foreach ($event->recipientIds() as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'ticket_comment',
                'title' => "{$senderName} replied on a ticket",
                'body' => $body,
                'data' => ['ticket_id' => $ticket->id],
            ]);
        }
    }
}
