<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FarmerAdded implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  Admin user IDs who should be notified.
     */
    public function __construct(
        public User $farmer,
        public User $fca,
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
            'type' => 'farmer_added',
            'farmer' => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
                'phone' => $this->farmer->phone,
                'email' => $this->farmer->email,
            ],
            'fca' => [
                'id' => $this->fca->id,
                'name' => $this->fca->name,
            ],
        ];
    }
}
