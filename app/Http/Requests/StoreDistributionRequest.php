<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('distributions.create');
    }

    public function rules(): array
    {
        return [
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'exists:tractors,id',
            'distributed_to' => 'required|exists:users,id',
            'tps_id' => 'nullable|exists:users,id',
            'area' => 'required|string|max:255',
            'distribution_date' => 'required|date',
            'return_date' => 'nullable|date|after:distribution_date',
            'notes' => 'nullable|string|max:1000',
            'proof_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }
}
