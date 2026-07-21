<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationAlertResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'metadata' => $this->meta,
            'acknowledged' => $this->is_acknowledged,
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'tractor' => $this->whenLoaded('tractor', fn () => $this->tractor ? [
                'id' => $this->tractor->id,
                'name' => $this->tractor->name,
                'plate_number' => $this->tractor->no_plate,
            ] : null),
            'device' => $this->whenLoaded('device', fn () => $this->device ? [
                'id' => $this->device->id,
                'imei' => $this->device->imei,
                'name' => $this->device->device_name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
