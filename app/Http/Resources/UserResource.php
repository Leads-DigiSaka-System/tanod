<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_country' => $this->phone_country,
            'country_code' => $this->country_code,
            'gender' => $this->gender,
            'profile_photo_url' => $this->profile_photo_path
                ? asset('storage/'.$this->profile_photo_path)
                : null,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'deletion_requested_at' => $this->deletion_requested_at,
            'deletion_scheduled_for' => $this->deletion_scheduled_for,
            'created_at' => $this->created_at,
        ];
    }
}
