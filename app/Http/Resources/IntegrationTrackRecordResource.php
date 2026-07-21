<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationTrackRecordResource extends JsonResource
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
            'tractor_id' => $this->device?->tractor?->id,
            'device_id' => $this->device_id,
            'imei' => $this->imei,
            'start' => [
                'latitude' => $this->start_lat !== null ? (float) $this->start_lat : null,
                'longitude' => $this->start_lng !== null ? (float) $this->start_lng : null,
                'recorded_at' => $this->start_time?->toIso8601String(),
            ],
            'end' => [
                'latitude' => $this->end_lat !== null ? (float) $this->end_lat : null,
                'longitude' => $this->end_lng !== null ? (float) $this->end_lng : null,
                'recorded_at' => $this->end_time?->toIso8601String(),
            ],
            'mileage_km' => $this->mileage,
            'runtime_seconds' => (int) $this->run_time_seconds,
            'runtime_hours' => $this->run_time_hours,
            'maximum_speed_kph' => $this->max_speed,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
