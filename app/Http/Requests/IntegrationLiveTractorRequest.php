<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegrationLiveTractorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'online' => ['nullable', 'boolean'],
            'include_without_location' => ['nullable', 'boolean'],
            'changed_since' => ['nullable', 'date'],
            'stale_after_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ];
    }
}
