<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'down_payment',
        'installments',
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
        'dr_photo_paths',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'pms_checklist',
        'collectible_status',
    ];

    protected function casts(): array
    {
        return [
            'service_charge' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'installments' => 'integer',
            'pms_checklist' => 'array',
            'dr_photo_paths' => 'array',
            'reported_date' => 'date',
            'resolved_at' => 'datetime',
            'collectible_status' => 'string',
        ];
    }

    protected static function booted(): void
    {
        // New ticket with status 'resolved' → goes to To Approve
        static::creating(function (self $ticket) {
            if ($ticket->status === 'resolved' && $ticket->collectible_status !== 'paid') {
                $ticket->collectible_status = 'to_approve';
            }
        });

        // Existing ticket updated to 'resolved' → goes to To Approve
        static::updating(function (self $ticket) {
            if ($ticket->isDirty('status') && $ticket->status === 'resolved' && $ticket->collectible_status !== 'paid') {
                $ticket->collectible_status = 'to_approve';
            }
        });
    }

    /**
     * Fallback fca_name from submitter when column is null.
     */
    public function getFcaNameAttribute(?string $value): ?string
    {
        if ($value) {
            return $value;
        }

        if (! $this->relationLoaded('submitter')) {
            $this->load('submitter');
        }

        return $this->submitter?->name;
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

    public function tractorParts(): BelongsToMany
    {
        return $this->belongsToMany(TractorPart::class, 'ticket_tractor_part')
            ->withPivot('amount', 'quantity')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TicketPayment::class)->latest('paid_at');
    }

    public function submitterContact(): string
    {
        return $this->submitter?->phone ?? '—';
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
