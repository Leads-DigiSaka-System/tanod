<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSimHistory extends Model
{
    protected $fillable = [
        'device_id',
        'changed_by',
        'old_sim',
        'new_sim',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
