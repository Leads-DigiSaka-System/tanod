<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  Admin user IDs who should be notified.
     */
    public function __construct(
        public Ticket $ticket,
        public array $recipientIds,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return collect($this->recipientIds)
            ->map(fn (int $id) => new PrivateChannel("notifications.{$id}"))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'ticket_created',
            'ticket' => [
                'id' => $this->ticket->id,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority,
                'status' => $this->ticket->status,
                'submitted_by' => $this->ticket->submitted_by,
                'created_at' => $this->ticket->created_at?->toIso8601String(),
            ],
        ];
    }
}
