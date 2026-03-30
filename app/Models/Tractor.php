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

    public function farmAssets()
    {
        return $this->hasMany(FarmAsset::class);
    }

    /* ── Helpers ── */

    public function isMaintenanceDue(): bool
    {
        if ($this->maintenance_km && $this->total_distance >= $this->maintenance_km) {
            $lastMaintenance = $this->maintenances()
                ->where('status', 'completed')
                ->latest('maintenance_date')
                ->first();

            if (!$lastMaintenance || $this->total_distance - ($lastMaintenance->km_at_maintenance ?? 0) >= $this->maintenance_km) {
                return true;
            }
        }

        return false;
    }
}
