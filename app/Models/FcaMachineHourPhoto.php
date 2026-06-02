<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcaMachineHourPhoto extends Model
{
    protected $fillable = [
        'fca_machine_hour_id',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function machineHour()
    {
        return $this->belongsTo(FcaMachineHour::class, 'fca_machine_hour_id');
    }
}
