<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmerFeedback extends Model
{
    use SoftDeletes;

    protected $table = 'farmer_feedbacks';

    protected $fillable = [
        'tractor_id',
        'booking_id',
        'submitted_by',
        'rating',
        'feedback',
        'conclusion',
        'category',
        'status',
        'admin_response',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
