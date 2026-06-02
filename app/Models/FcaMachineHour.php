<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcaMachineHour extends Model
{
    protected $fillable = [
        'user_fca_id',
        'entry_order',
        'date_visited',
        'machine_hours',
        'gps_status',
        'in_charge_user_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_order' => 'integer',
            'date_visited' => 'date',
            'machine_hours' => 'integer',
            'in_charge_user_id' => 'integer',
        ];
    }

    public function userFca()
    {
        return $this->belongsTo(UserFca::class);
    }

    public function inChargeUser()
    {
        return $this->belongsTo(User::class, 'in_charge_user_id');
    }

    public function photos()
    {
        return $this->hasMany(FcaMachineHourPhoto::class)->orderBy('sort_order')->orderBy('id');
    }
}
