<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('feedback.create');
    }

    public function rules(): array
    {
        return [
            'tractor_id' => 'required|exists:tractors,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string|max:2000',
        ];
    }
}
