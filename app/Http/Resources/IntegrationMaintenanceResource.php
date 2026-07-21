<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationMaintenanceResource extends JsonResource
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
            'tractor_id' => $this->tractor_id,
            'maintenance_date' => $this->maintenance_date?->toDateString(),
            'status' => $this->status,
            'issue_type' => $this->whenLoaded('issueType', fn () => $this->issueType ? [
                'id' => $this->issueType->id,
                'name' => $this->issueType->name,
            ] : null),
            'description' => $this->description,
            'conclusion' => $this->conclusion,
            'cost' => $this->cost,
            'distance_km' => $this->km_at_maintenance,
            'running_hours' => $this->hours_at_maintenance,
            'pms_checklist' => $this->pms_checklist,
            'request_notes' => $this->request_notes,
            'technician' => [
                'name' => $this->tech_name,
                'email' => $this->tech_email,
                'phone' => $this->tech_phone,
            ],
            'performed_by' => $this->whenLoaded('performer', fn () => $this->performer ? [
                'id' => $this->performer->id,
                'name' => $this->performer->name,
            ] : null),
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
