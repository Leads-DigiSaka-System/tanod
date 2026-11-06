<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertSummary extends Model
{
    protected $table = 'alerts_summary';

    protected $fillable = [
        'total_alerts',
        'unacknowledged_alerts',
        'by_type',
    ];

    protected function casts(): array
    {
        return [
            'total_alerts' => 'integer',
            'unacknowledged_alerts' => 'integer',
            'by_type' => 'array',
        ];
    }
}
