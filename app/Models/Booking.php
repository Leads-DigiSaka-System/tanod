<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tractor_id',
        'slot_id',
        'booked_by',
        'farmer_id',
        'approved_by',
        'booking_date',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'purpose',
        'farm_area_hectares',
        'cost',
        'notes',
        'rejection_reason',
        'kilometer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'kilometer' => 'float',
            'farm_area_hectares' => 'float',
            'cost' => 'float',
        ];
    }

    /* ── Relationships ── */

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function slot()
    {
        return $this->belongsTo(BookingSlot::class, 'slot_id');
    }

    public function bookedBy()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function feedback()
    {
        return $this->hasOne(FarmerFeedback::class);
    }

    /* ── Scopes ── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
