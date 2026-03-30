<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tractor_id',
        'device_id',
        'submitted_by',
        'assigned_to',
        'subject',
        'description',
        'priority',
        'status',
    ];

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }
}
