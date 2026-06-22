<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'imei',
        'device_name',
        'device_model',
        'sim',
        'sim_iccid',
        'sim_registration_code',
        'mc_type',
        'mc_type_use_scope',
        'mobile_data_load',
        'activation_time',
        'sales_time',
        'subscription_expiration',
        'expiration_date',
        'remark',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'activation_time' => 'datetime',
            'sales_time' => 'datetime',
            'expiration_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /* ── Relationships ── */

    public function tractor()
    {
        return $this->hasOne(Tractor::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(DeviceLocation::class)->latestOfMany();
    }

    public function locations()
    {
        return $this->hasMany(DeviceLocation::class);
    }

    public function trackRecords()
    {
        return $this->hasMany(DeviceTrackRecord::class);
    }

    public function geoFences()
    {
        return $this->belongsToMany(GeoFence::class, 'device_geo_fence')->withTimestamps();
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /* ── Scopes ── */

    /**
     * Scope to exclude devices whose latest location heartbeat is older than N days.
     * Also excludes devices that have never reported any location.
     */
    public function scopeNotStale(Builder $query, int $days = 365): void
    {
        $cutoff = now()->subDays($days);

        $query->whereHas('latestLocation', function ($q) use ($cutoff) {
            $q->where(function ($q) use ($cutoff) {
                $q->whereNotNull('heartbeat_at')
                    ->where('heartbeat_at', '>=', $cutoff);
            })->orWhere(function ($q) use ($cutoff) {
                $q->whereNull('heartbeat_at')
                    ->where('created_at', '>=', $cutoff);
            });
        });
    }

    /* ── Helpers ── */

    public function isOnline(): bool
    {
        $loc = $this->latestLocation;

        return $loc && $loc->status === 1;
    }
}
