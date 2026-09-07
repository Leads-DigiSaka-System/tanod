<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bookings.create');
    }

    public function rules(): array
    {
        return [
            'is_member'         => 'required|boolean',
            'fca_id'            => 'required|exists:users,id',
            'tractor_id'        => 'required|exists:tractors,id',
            'farmer_id'         => 'nullable|required_if:is_member,true|exists:users,id',
            'contact_name'      => 'nullable|required_if:is_member,false|string|max:255',
            'contact_phone'     => 'nullable|required_if:is_member,false|string|max:20',
            'start_date'        => 'required|date|after_or_equal:today',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'purpose'           => 'required|string|max:500',
            'farm_area_hectares'=> 'nullable|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
