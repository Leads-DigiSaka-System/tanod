<?php

namespace App\Events;

use App\Models\TicketComment;
use App\Models\User;
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
     * All participant IDs who should be notified (excludes the commenter).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function recipientIds(): \Illuminate\Support\Collection
    {
        $ticket = $this->comment->ticket;
        $ticket->loadMissing('tractor');

        $adminIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id');

        // TSR users via tractor group membership
        $tsrIds = collect();
        if ($ticket->tractor_id) {
            $tsrIds = collect(User::tsrIdsForTractor($ticket->tractor_id));
        }

        return $ticket->assignees()->pluck('users.id')
            ->merge([$ticket->submitted_by, $ticket->assigned_to])
            ->merge($adminIds)
            ->merge($tsrIds)
            ->when($ticket->tractor, fn ($col) => $col->merge([$ticket->tractor->assigned_to]))
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $this->comment->user_id);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("ticket.{$this->comment->ticket_id}"),
        ];

        foreach ($this->recipientIds() as $userId) {
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
            ? request()->getSchemeAndHttpHost().'/storage/'.$this->comment->attachment_path
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
