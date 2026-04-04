<?php

namespace App\Events;

use App\Models\TicketComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentAdded implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketComment $comment,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("ticket.{$this->comment->ticket_id}"),
        ];

        // Also notify all participants (assignees + submitter) except the commenter.
        $ticket = $this->comment->ticket;

        $recipientIds = $ticket->assignees()->pluck('users.id')
            ->merge([$ticket->submitted_by])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $this->comment->user_id);

        foreach ($recipientIds as $userId) {
            $channels[] = new PrivateChannel("notifications.{$userId}");
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $attachmentUrl = $this->comment->attachment_path
            ? rtrim(config('app.url'), '/').'/storage/'.$this->comment->attachment_path
            : null;

        return [
            'comment' => [
                'id' => $this->comment->id,
                'ticket_id' => $this->comment->ticket_id,
                'body' => $this->comment->body,
                'attachment_path' => $this->comment->attachment_path,
                'attachment_url' => $attachmentUrl,
                'user' => [
                    'id' => $this->comment->user->id,
                    'name' => $this->comment->user->name,
                ],
                'created_at' => $this->comment->created_at?->toIso8601String(),
            ],
        ];
    }
}
