<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_country',
        'country_code',
        'gender',
        'profile_photo_path',
        'password',
        'must_change_password',
        'fcm_token',
        'device_type',
        'is_active',
        'fca_id',
        'tps_assign_all_tractors',
        'province',
        'city',
        'barangay',
        'organization_name',
        'deletion_requested_at',
        'deletion_scheduled_for',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'tps_assign_all_tractors' => 'boolean',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_for' => 'datetime',
        ];
    }

    /* ── Relationships ── */

    public function fca()
    {
        return $this->belongsTo(User::class, 'fca_id');
    }

    public function farmers()
    {
        return $this->hasMany(User::class, 'fca_id');
    }

    public function fcaProfile()
    {
        return $this->hasOne(UserFca::class);
    }

    public function groups()
    {
        return $this->belongsToMany(TractorGroup::class, 'group_user', 'user_id', 'tractor_group_id')
            ->withTimestamps();
    }

    public function assignedTractors()
    {
        return $this->hasMany(Tractor::class, 'assigned_to');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'booked_by');
    }

    public function approvedBookings()
    {
        return $this->hasMany(Booking::class, 'approved_by');
    }

    public function distributions()
    {
        return $this->hasMany(TractorDistribution::class, 'distributed_by');
    }

    public function receivedDistributions()
    {
        return $this->hasMany(TractorDistribution::class, 'distributed_to');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function submittedTickets()
    {
        return $this->hasMany(Ticket::class, 'submitted_by');
    }

    public function feedbacks()
    {
        return $this->hasMany(FarmerFeedback::class, 'submitted_by');
    }

    public function supportContact()
    {
        return $this->hasOne(SupportContact::class);
    }

    public function hasFullTpsTractorAccess(): bool
    {
        return $this->hasRole('tps') && $this->tps_assign_all_tractors;
    }

    /**
     * @return array<int>
     */
    public function accessibleTractorIds(): array
    {
        if ($this->hasAnyRole(['super-admin', 'sub-admin'])) {
            return Tractor::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($this->hasRole('tps')) {
            if ($this->hasFullTpsTractorAccess()) {
                return Tractor::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
            }

            $groupTractorIds = Tractor::query()
                ->whereHas('groups.users', fn (Builder $query) => $query->where('users.id', $this->id))
                ->pluck('id');

            $distributionTractorIds = TractorDistribution::query()
                ->where('tps_id', $this->id)
                ->where('status', 'distributed')
                ->pluck('tractor_id');

            return $groupTractorIds->merge($distributionTractorIds)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ($this->hasRole('fca')) {
            return Tractor::query()
                ->whereHas('distributions', fn (Builder $query) => $query->where('distributed_to', $this->id)
                    ->where('status', 'distributed'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($this->hasRole('farmer') && $this->fca_id) {
            return Tractor::query()
                ->whereHas('distributions', fn (Builder $query) => $query->where('distributed_to', $this->fca_id)
                    ->where('status', 'distributed'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    /**
     * @return array<int>
     */
    public static function tpsIdsForTractor(int $tractorId): array
    {
        $assignedTpsIds = static::query()
            ->role('tps')
            ->where('is_active', true)
            ->where(function (Builder $query) use ($tractorId) {
                $query->where('tps_assign_all_tractors', true)
                    ->orWhereHas('groups.tractors', fn (Builder $groupQuery) => $groupQuery->where('tractors.id', $tractorId));
            })
            ->pluck('id');

        $distributionTpsIds = TractorDistribution::query()
            ->where('tractor_id', $tractorId)
            ->where('status', 'distributed')
            ->whereNotNull('tps_id')
            ->pluck('tps_id');

        return $assignedTpsIds->merge($distributionTpsIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
