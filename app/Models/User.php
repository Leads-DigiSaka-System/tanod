<?php

namespace App\Models;

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
}
