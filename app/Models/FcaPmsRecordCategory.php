<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaPmsRecordCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'fca_pms_record_id',
        'category',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function pmsRecord()
    {
        return $this->belongsTo(FcaPmsRecord::class, 'fca_pms_record_id');
    }
    //
}
