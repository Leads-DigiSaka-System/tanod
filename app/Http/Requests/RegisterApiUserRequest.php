<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterApiUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|string|in:farmer,fca,tps|exists:roles,name',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'nullable|string|regex:/^09\d{9}$/|max:11|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'device_name' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (empty($this->email) && empty($this->phone)) {
                $validator->errors()->add('email', 'Either email or phone number is required.');
            }
        });
    }
}
