<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeoFenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('geofences.create');
    }

    public function rules(): array
    {
        return [
            'device_ids' => 'required|array|min:1',
            'device_ids.*' => 'exists:devices,id',
            'name' => 'required|string|max:255',
            'shape' => 'required|in:circle,polygon',
            'center_lat' => 'required_if:shape,circle|nullable|numeric|between:-90,90',
            'center_lng' => 'required_if:shape,circle|nullable|numeric|between:-180,180',
            'radius' => 'required_if:shape,circle|nullable|numeric|min:50|max:100000',
            'coordinates' => 'required_if:shape,polygon|nullable|array|min:3',
            'coordinates.*.lat' => 'required|numeric|between:-90,90',
            'coordinates.*.lng' => 'required|numeric|between:-180,180',
            'alert_on' => 'required|in:enter,exit,both',
        ];
    }
}
