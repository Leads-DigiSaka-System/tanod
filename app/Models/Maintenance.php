<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tractor_id',
        'issue_type_id',
        'maintenance_date',
        'tech_name',
        'tech_email',
        'tech_phone',
        'farmer_name',
        'farmer_email',
        'farmer_phone',
        'description',
        'conclusion',
        'cost',
        'km_at_maintenance',
        'hours_at_maintenance',
        'status',
        'performed_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'cost' => 'float',
            'km_at_maintenance' => 'float',
            'hours_at_maintenance' => 'float',
        ];
    }

    /* ── Relationships ── */

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function issueType()
    {
        return $this->belongsTo(IssueType::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images()
    {
        return $this->hasMany(MaintenanceImage::class);
    }
}
