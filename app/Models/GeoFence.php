<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoFence extends Model
{
    protected $fillable = [
        'name',
        'shape',
        'center_lat',
        'center_lng',
        'radius',
        'coordinates',
        'alert_on',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'center_lat' => 'float',
            'center_lng' => 'float',
            'coordinates' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_geo_fence')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
