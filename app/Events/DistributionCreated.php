<?php

namespace App\Events;

use App\Models\TractorDistribution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DistributionCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  FCA recipient + admin IDs.
     */
    public function __construct(
        public TractorDistribution $distribution,
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
            'type' => 'distribution_created',
            'distribution' => [
                'id' => $this->distribution->id,
                'tractor_id' => $this->distribution->tractor_id,
                'distributed_to' => $this->distribution->distributed_to,
                'status' => $this->distribution->status,
                'distribution_date' => $this->distribution->distribution_date?->toDateString(),
                'created_at' => $this->distribution->created_at?->toIso8601String(),
            ],
        ];
    }
}
