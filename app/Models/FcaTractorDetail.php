<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaTractorDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_fca_id',
        'tractor_id',
        'tractor_model',
        'front_loader_serial_number',
        'dr_number',
        'rotavator_serial_number',
        'serial_number',
        'disk_plow_serial_number',
        'engine_number',
        'gps_imei',
        'gps_sim_number',
        'gps_mobile_number',
    ];

    protected function casts(): array
    {
        return [
            'tractor_id' => 'integer',
        ];
    }

    public function userFca()
    {
        return $this->belongsTo(UserFca::class);
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }
    //
}
