<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaPmsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_fca_id',
        'column_order',
        'actual_hours',
        'performed_by',
        'in_charge_user_id',
    ];

    protected function casts(): array
    {
        return [
            'column_order' => 'integer',
            'actual_hours' => 'integer',
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

    public function categories()
    {
        return $this->hasMany(FcaPmsRecordCategory::class)->orderBy('sort_order')->orderBy('id');
    }
    //
}
