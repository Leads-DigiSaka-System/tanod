<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TractorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'imei' => $this->imei,
            'no_plate' => $this->no_plate,
            'id_no' => $this->id_no,
            'engine_no' => $this->engine_no,
            'chassis_no' => $this->chassis_no,
            'brand' => $this->brand,
            'model' => $this->model,
            'fuel_consumption' => $this->fuel_consumption,
            'manufacture_date' => $this->manufacture_date,
            'installation_time' => $this->installation_time,
            'installation_address' => $this->installation_address,
            'max_speed' => $this->max_speed,
            'maintenance_km' => $this->maintenance_km,
            'maintenance_hours' => $this->maintenance_hours,
            'total_distance' => $this->effective_total_distance,
            'total_running_hours' => $this->effective_running_hours,
            'next_pms_hours' => $this->nextPmsHours(),
            'pms_status' => $this->pmsStatus(),
            'is_maintenance_due' => $this->isMaintenanceDue(),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'groups' => $this->whenLoaded('groups', fn () => $this->groups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
            ])),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset('storage/'.$img->path),
                'sort_order' => $img->sort_order,
            ])),
            'maintenances' => TractorResource::collection($this->whenLoaded('maintenances')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
