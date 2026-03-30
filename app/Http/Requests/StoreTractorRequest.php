<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('tractors.create');
    }

    public function rules(): array
    {
        return [
            'imei' => 'required|string|unique:tractors,imei',
            'no_plate' => 'required|string|max:50',
            'id_no' => 'required|string|max:100',
            'engine_no' => 'required|string|max:100',
            'chassis_no' => 'nullable|string|max:100',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'fuel_consumption' => 'required|numeric|min:0',
            'manufacture_date' => 'nullable|date',
            'installation_time' => 'required|date',
            'installation_address' => 'required|string|max:500',
            'max_speed' => 'nullable|numeric|min:0',
            'maintenance_km' => 'nullable|numeric|min:0',
            'maintenance_hours' => 'nullable|numeric|min:0',
            'running_hours' => 'nullable|numeric|min:0',
            'total_distance' => 'nullable|numeric|min:0',
            'device_id' => 'nullable|exists:devices,id',
            'assigned_to' => 'nullable|exists:users,id',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
