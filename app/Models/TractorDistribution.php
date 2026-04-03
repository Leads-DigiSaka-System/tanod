<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorDistribution extends Model
{
    protected $fillable = [
        'tractor_id',
        'distributed_to',
        'distributed_by',
        'area',
        'notes',
        'distribution_date',
        'return_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function distributedToUser()
    {
        return $this->belongsTo(User::class, 'distributed_to');
    }

    public function distributedByUser()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
