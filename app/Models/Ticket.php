<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'service_charge',
        'priority',
        'status',
        'category',
        'tractor_name',
        'fca_name',
        'reported_date',
        'photo_path',
        'nameplate_photo_path',
        'dashboard_photo_path',
        'resolution_photo_path',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'service_charge' => 'decimal:2',
            'reported_date' => 'date',
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

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }

    public function latestComment(): HasOne
    {
        return $this->hasOne(TicketComment::class)->latestOfMany();
    }

    public function damagePhotos(): HasMany
    {
        return $this->hasMany(TicketDamagePhoto::class)->orderBy('sort_order');
    }

    public function userCanAccessChannel(User $user): bool
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return true;
        }

        if (
            $this->submitted_by === $user->id ||
            $this->assigned_to === $user->id ||
            $this->assignees()->where('users.id', $user->id)->exists()
        ) {
            return true;
        }

        if (! $this->tractor_id) {
            return false;
        }

        if ($user->hasRole('tps')) {
            return in_array($this->tractor_id, $user->accessibleTractorIds(), true);
        }

        if ($user->hasRole('fca')) {
            return TractorDistribution::query()
                ->where('tractor_id', $this->tractor_id)
                ->where('distributed_to', $user->id)
                ->where('status', 'distributed')
                ->exists();
        }

        if ($user->hasRole('farmer')) {
            return TractorDistribution::query()
                ->where('tractor_id', $this->tractor_id)
                ->where('distributed_to', $user->fca_id)
                ->where('status', 'distributed')
                ->exists();
        }

        return false;
    }
}
