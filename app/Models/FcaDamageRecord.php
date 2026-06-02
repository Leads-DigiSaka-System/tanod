<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaDamageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_fca_id',
        'entry_order',
        'unit',
        'operational_after_repair',
        'date_damaged',
        'date_repaired',
        'nature_of_problem',
        'cause_of_damage',
        'parts_replaced',
        'in_charge_user_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_order' => 'integer',
            'date_damaged' => 'date',
            'date_repaired' => 'date',
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
    //
}
