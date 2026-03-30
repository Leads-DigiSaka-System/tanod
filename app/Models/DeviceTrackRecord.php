<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceTrackRecord extends Model
{
    protected $fillable = [
        'device_id',
        'imei',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'mileage',
        'run_time_seconds',
        'max_speed',
        'start_time',
        'end_time',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'mileage' => 'float',
            'max_speed' => 'float',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function getRunTimeHoursAttribute(): float
    {
        return round($this->run_time_seconds / 3600, 2);
    }
}
