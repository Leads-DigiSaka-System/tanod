<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('groups.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:tractor_groups,name,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string|max:1000',
            'area' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'tractor_ids' => 'nullable|array',
            'tractor_ids.*' => 'exists:tractors,id',
            'assign_all_tps' => 'boolean',
            'tps_user_ids' => 'nullable|array',
            'tps_user_ids.*' => 'exists:users,id',
        ];
    }
}
