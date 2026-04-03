<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorDistribution extends Model
{
    protected $fillable = [
        'tractor_id',
        'tractor_ids',
        'distributed_to',
        'distributed_by',
        'tps_id',
        'area',
        'notes',
        'proof_photo',
        'latitude',
        'longitude',
        'distribution_date',
        'return_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tractor_ids' => 'array',
            'distribution_date' => 'date',
            'return_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function tractors()
    {
        return Tractor::whereIn('id', $this->tractor_ids ?? [])->get();
    }

    public function distributedToUser()
    {
        return $this->belongsTo(User::class, 'distributed_to');
    }

    public function distributedByUser()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function tpsUser()
    {
        return $this->belongsTo(User::class, 'tps_id');
    }

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
