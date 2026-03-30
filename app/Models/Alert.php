<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'device_id',
        'tractor_id',
        'geo_fence_id',
        'type',
        'title',
        'message',
        'meta',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function geoFence()
    {
        return $this->belongsTo(GeoFence::class);
    }

    public function acknowledger()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }
}
