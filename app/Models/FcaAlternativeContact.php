<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaAlternativeContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_fca_id',
        'entry_order',
        'phone',
        'last_name',
        'first_name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'entry_order' => 'integer',
        ];
    }

    public function userFca()
    {
        return $this->belongsTo(UserFca::class);
    }
    //
}
