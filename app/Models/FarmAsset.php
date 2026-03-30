<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'serial_number',
        'description',
        'tractor_id',
        'assigned_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
