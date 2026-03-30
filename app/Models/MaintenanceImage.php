<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceImage extends Model
{
    protected $fillable = ['maintenance_id', 'path', 'type'];

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }
}
