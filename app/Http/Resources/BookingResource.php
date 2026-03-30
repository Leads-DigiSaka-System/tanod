<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tractor' => $this->whenLoaded('tractor', fn () => [
                'id' => $this->tractor->id,
                'no_plate' => $this->tractor->no_plate,
                'brand' => $this->tractor->brand,
                'model' => $this->tractor->model,
            ]),
            'booked_by' => new UserResource($this->whenLoaded('bookedBy')),
            'farmer' => new UserResource($this->whenLoaded('farmer')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'booking_date' => $this->booking_date,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'purpose' => $this->purpose,
            'farm_area_hectares' => $this->farm_area_hectares,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
