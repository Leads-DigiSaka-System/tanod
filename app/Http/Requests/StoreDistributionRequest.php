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
            'tractor_id' => 'required|exists:tractors,id',
            'distributed_to' => 'required|exists:users,id',
            'area' => 'required|string|max:255',
            'distribution_date' => 'required|date',
            'return_date' => 'nullable|date|after:distribution_date',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
