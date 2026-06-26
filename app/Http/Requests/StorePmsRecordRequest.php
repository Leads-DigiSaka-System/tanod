<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePmsRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['fca', 'tps']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tractor_id' => ['required', 'exists:tractors,id'],
            'type' => ['required', 'in:record,request'],
            'hours_at_maintenance' => ['nullable', 'numeric', 'min:0'],
            'km_at_maintenance' => ['nullable', 'numeric', 'min:0'],
            'pms_checklist' => ['nullable', 'array'],
            'pms_checklist.*.name' => ['required_with:pms_checklist', 'string'],
            'pms_checklist.*.done' => ['required_with:pms_checklist', 'boolean'],
            'pms_checklist.*.notes' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'request_notes' => ['nullable', 'string', 'max:2000'],
            'nameplate_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'dashboard_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'damage_photos' => ['nullable', 'array', 'max:10'],
            'damage_photos.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }
}
