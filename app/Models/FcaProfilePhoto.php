<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaProfilePhoto extends Model
{
    use HasFactory;

    public const CATEGORY_TRACTOR = 'tractor';

    public const CATEGORY_LOGBOOK = 'logbook';

    protected $fillable = [
        'user_fca_id',
        'category',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function userFca()
    {
        return $this->belongsTo(UserFca::class);
    }
    //
}
