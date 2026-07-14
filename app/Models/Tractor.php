<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tractor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'imei',
        'no_plate',
        'name',
        'id_no',
        'engine_no',
        'chassis_no',
        'brand',
        'model',
        'fuel_consumption',
        'manufacture_date',
        'installation_time',
        'installation_address',
        'max_speed',
        'maintenance_km',
        'maintenance_hours',
        'total_distance',
        'running_hours',
        'insurance_effective_date',
        'insurance_expiry_date',
        'dr_no',
        'dr_date',
        'actual_delivery_date',
        'front_loader_sn',
        'rotary_tiller_sn',
        'disc_plow_sn',
        'device_id',
        'assigned_to',
        'created_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fuel_consumption' => 'float',
            'max_speed' => 'float',
            'maintenance_km' => 'float',
            'maintenance_hours' => 'float',
            'total_distance' => 'float',
            'running_hours' => 'float',
            'manufacture_date' => 'date',
            'installation_time' => 'datetime',
            'insurance_effective_date' => 'date',
            'insurance_expiry_date' => 'date',
            'dr_date' => 'date',
            'actual_delivery_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /* ── Relationships ── */

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images()
    {
        return $this->hasMany(TractorImage::class)->orderBy('sort_order');
    }

    public function groups()
    {
        return $this->belongsToMany(TractorGroup::class, 'group_tractor', 'tractor_id', 'tractor_group_id')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function distributions()
    {
        return $this->hasMany(TractorDistribution::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function farmAssets()
    {
        return $this->hasMany(FarmAsset::class);
    }

    public function trackRecords()
    {
        return $this->hasManyThrough(
            DeviceTrackRecord::class,
            Device::class,
            'id',        // devices.id
            'device_id', // device_track_records.device_id
            'device_id', // tractors.device_id
            'id'         // devices.id
        );
    }

    /* ── Computed Accessors ── */

    /**
     * Prefer device-tracked running hours over the manual DB value.
     */
    public function getEffectiveRunningHoursAttribute(): float
    {
        if (isset($this->attributes['track_records_sum_run_time_seconds'])
            && $this->attributes['track_records_sum_run_time_seconds'] > 0) {
            return round($this->attributes['track_records_sum_run_time_seconds'] / 3600, 2);
        }

        return (float) ($this->running_hours ?? 0);
    }

    /**
     * Use the higher of manual total_distance or device-tracked mileage.
     */
    public function getEffectiveTotalDistanceAttribute(): float
    {
        $manual = (float) ($this->total_distance ?? 0);
        $computed = isset($this->attributes['track_records_sum_mileage'])
            ? round((float) $this->attributes['track_records_sum_mileage'], 2)
            : 0.0;

        return max($manual, $computed);
    }

    /* ── Helpers ── */

    public function isMaintenanceDue(): bool
    {
        $distance = $this->effective_total_distance;

        if ($this->maintenance_km && $distance >= $this->maintenance_km) {
            $lastMaintenance = $this->maintenances()
                ->where('status', 'completed')
                ->latest('maintenance_date')
                ->first();

            if (! $lastMaintenance || $distance - ($lastMaintenance->km_at_maintenance ?? 0) >= $this->maintenance_km) {
                return true;
            }
        }

        return false;
    }

    /**
     * PMS milestones in hours: 50, 100, 200, 300, then every 300 hours.
     */
    public function nextPmsHours(): ?float
    {
        $milestones = [50, 100, 200, 300];
        $hours = $this->effective_running_hours;

        foreach ($milestones as $m) {
            if ($hours < $m) {
                return $m;
            }
        }

        // After 300h, every 300h interval
        $interval = 300;
        $next = $interval * (floor($hours / $interval) + 1);

        return $next;
    }

    /**
     * PMS status: 'due', 'upcoming', or 'ok'.
     * Due = hours >= next milestone, Upcoming = within 10h of next milestone.
     */
    public function pmsStatus(): string
    {
        $next = $this->nextPmsHours();
        if ($next === null) {
            return 'ok';
        }

        $hours = $this->effective_running_hours;

        if ($hours >= $next) {
            return 'due';
        }

        if ($next - $hours <= 10) {
            return 'upcoming';
        }

        return 'ok';
    }
}
