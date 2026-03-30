<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TractorGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'area',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /* ── Relationships ── */

    public function tractors()
    {
        return $this->belongsToMany(Tractor::class, 'group_tractor', 'tractor_group_id', 'tractor_id')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user', 'tractor_group_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function tpsUsers()
    {
        return $this->users()->wherePivot('role', 'tps');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
