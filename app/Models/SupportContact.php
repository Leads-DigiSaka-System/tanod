<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportContact extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provinces()
    {
        return $this->belongsToMany(PhilippineProvince::class, 'province_support_contact', 'user_id', 'province_code', 'user_id', 'province_code')
            ->withTimestamps();
    }
}
