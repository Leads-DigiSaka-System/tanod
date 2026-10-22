<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationLocationResource extends JsonResource
{
    /**
     * Serial number of the tractor (set before collection).
     */
    public static ?string $serialNo = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $speed = (float) ($this->speed ?? 0);
        $ignitionOn = (bool) ($this->acc_status ?? false);
        $heartbeatAt = $this->heartbeat_at;

        // Determine status: Offline, Parked, Idle, Moving
        // Offline = no heartbeat or heartbeat > 10 min ago
        // Parked = online, ignition off
        // Moving = online, ignition on, speed >= 3 km/h
        // Idle = online, ignition on, not moving
        $status = 'Offline';
        if ($heartbeatAt) {
            $minutesAgo = now()->diffInMinutes($heartbeatAt);
            if ($minutesAgo <= 10) {
                if (! $ignitionOn) {
                    $status = 'Parked';
                } elseif ($speed >= 3.0) {
                    $status = 'Moving';
                } else {
                    $status = 'Idle';
                }
            }
        }

        return [
            'id' => $this->id,
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'speed_kph' => $speed,
            'direction_degrees' => $this->direction,
            'ignition_on' => $ignitionOn,
            'gps_satellites' => $this->gps_num,
            'position_source' => $this->pos_type,
            'recorded_at' => $heartbeatAt?->toIso8601String(),
            'Status' => $status,
            'Serial_no' => static::$serialNo ?? null,
        ];
    }
}
