<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'speed_kph' => $this->speed,
            'direction_degrees' => $this->direction,
            'ignition_on' => (bool) $this->acc_status,
            'gps_satellites' => $this->gps_num,
            'position_source' => $this->pos_type,
            'recorded_at' => ($this->heartbeat_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
