<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $recipientIds
     */
    public function __construct(
        public Alert $alert,
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
            'type' => 'alert',
            'alert' => [
                'id' => $this->alert->id,
                'type' => $this->alert->type,
                'title' => $this->alert->title,
                'message' => $this->alert->message,
                'tractor_id' => $this->alert->tractor_id,
                'device_id' => $this->alert->device_id,
                'created_at' => $this->alert->created_at?->toIso8601String(),
            ],
        ];
    }
}
