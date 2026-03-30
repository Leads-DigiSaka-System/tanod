<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLocation extends Model
{
    protected $fillable = [
        'device_id',
        'imei',
        'lat',
        'lng',
        'speed',
        'direction',
        'status',
        'acc_status',
        'gps_num',
        'pos_type',
        'heartbeat_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'speed' => 'float',
            'heartbeat_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
