<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'imei' => $this->imei,
            'device_name' => $this->device_name,
            'device_model' => $this->device_model,
            'sim' => $this->sim,
            'is_active' => $this->is_active,
            'activation_time' => $this->activation_time,
            'expiration_date' => $this->expiration_date,
            'online' => $this->isOnline(),
            'tractor' => $this->whenLoaded('tractor', fn () => [
                'id' => $this->tractor->id,
                'no_plate' => $this->tractor->no_plate,
            ]),
            'location' => $this->whenLoaded('latestLocation', fn () => [
                'lat' => $this->latestLocation->lat,
                'lng' => $this->latestLocation->lng,
                'speed' => $this->latestLocation->speed,
                'direction' => $this->latestLocation->direction,
                'heartbeat_at' => $this->latestLocation->heartbeat_at,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
