<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  Admin/FCA user IDs.
     */
    public function __construct(
        public Booking $booking,
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
            'type' => 'booking_created',
            'booking' => [
                'id' => $this->booking->id,
                'tractor_id' => $this->booking->tractor_id,
                'booking_date' => $this->booking->booking_date?->toDateString(),
                'purpose' => $this->booking->purpose,
                'status' => $this->booking->status,
                'booked_by' => $this->booking->booked_by,
                'created_at' => $this->booking->created_at?->toIso8601String(),
            ],
        ];
    }
}
