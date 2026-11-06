<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationTractorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $location = $this->whenLoaded('device', fn () => $this->device?->latestLocation);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'plate_number' => $this->no_plate,
            'imei' => $this->imei,
            'identifiers' => [
                'id_number' => $this->id_no,
                'engine_number' => $this->engine_no,
                'chassis_number' => $this->chassis_no,
            ],
            'machine' => [
                'brand' => $this->brand,
                'model' => $this->model,
                'manufactured_on' => $this->manufacture_date?->toDateString(),
                'fuel_consumption_l_per_100km' => $this->fuel_consumption,
                'maximum_speed_kph' => $this->max_speed,
            ],
            'implements' => [
                'front_loader_serial_number' => $this->front_loader_sn,
                'rotary_tiller_serial_number' => $this->rotary_tiller_sn,
                'disc_plow_serial_number' => $this->disc_plow_sn,
            ],
            'installation' => [
                'installed_at' => $this->installation_time?->toIso8601String(),
                'address' => $this->installation_address,
            ],
            'usage' => [
                'total_distance_km' => $this->effective_total_distance,
                'running_hours' => $this->effective_running_hours,
                'maintenance_interval_km' => $this->maintenance_km,
                'maintenance_interval_hours' => $this->maintenance_hours,
                'next_pms_hours' => $this->nextPmsHours(),
                'pms_status' => $this->pmsStatus(),
                'maintenance_due' => $this->isMaintenanceDue(),
            ],
            'delivery' => [
                'delivery_receipt_number' => $this->dr_no,
                'delivery_receipt_date' => $this->dr_date?->toDateString(),
                'actual_delivery_date' => $this->actual_delivery_date?->toDateString(),
            ],
            'insurance' => [
                'effective_date' => $this->insurance_effective_date?->toDateString(),
                'expiry_date' => $this->insurance_expiry_date?->toDateString(),
            ],
            'active' => $this->is_active,
            'device' => $this->whenLoaded('device', fn () => $this->device ? [
                'id' => $this->device->id,
                'imei' => $this->device->imei,
                'name' => $this->device->device_name,
                'model' => $this->device->device_model,
                'active' => $this->device->is_active,
                'online' => $this->device->isOnline(),
                'last_seen_at' => $location?->heartbeat_at?->toIso8601String(),
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'organization_name' => $this->assignee->organization_name,
            ] : null),
            'groups' => $this->whenLoaded('groups', fn () => $this->groups->map(fn ($group): array => [
                'id' => $group->id,
                'name' => $group->name,
            ])),
            'Last_Position' => $location ? [
                'Longitude' => $location->lng,
                'Latitude' => $location->lat,
            ] : null,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image): array => [
                'id' => $image->id,
                'type' => $image->type,
                'url' => url('/storage/'.$image->path),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
