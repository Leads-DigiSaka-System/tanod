<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance.create');
    }

    public function rules(): array
    {
        return [
            'tractor_id' => 'required|exists:tractors,id',
            'issue_type_id' => 'nullable|exists:issue_types,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status' => 'required|in:documentation,scheduled,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
            'cost' => 'nullable|numeric|min:0',
            'odometer_reading' => 'nullable|numeric|min:0',
            'hours_reading' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
