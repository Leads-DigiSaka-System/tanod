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
        'category',
        'photo_path',
        'resolution_photo_path',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

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

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'ticket_assignees')->withTimestamps();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }
}
