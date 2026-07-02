<?php

namespace App\Events;

use App\Models\FarmerFeedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  FCA / TSR user IDs who should be notified.
     */
    public function __construct(
        public FarmerFeedback $feedback,
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
            'type' => 'feedback_created',
            'feedback' => [
                'id' => $this->feedback->id,
                'rating' => $this->feedback->rating,
                'feedback' => $this->feedback->feedback,
                'category' => $this->feedback->category,
                'status' => $this->feedback->status,
                'tractor_id' => $this->feedback->tractor_id,
                'submitted_by' => $this->feedback->submitted_by,
                'created_at' => $this->feedback->created_at?->toIso8601String(),
            ],
        ];
    }
}
