<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorImage extends Model
{
    protected $fillable = ['tractor_id', 'path', 'sort_order'];

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }
}
