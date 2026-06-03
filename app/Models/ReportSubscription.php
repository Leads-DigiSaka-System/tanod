<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'report_type',
        'interval',
        'day_of_week',
        'day_of_month',
        'time',
        'last_sent_at',
        'next_scheduled_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'next_scheduled_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function reportTypes(): array
    {
        return [
            'tractor-usage' => 'Tractor Usage',
            'maintenance-summary' => 'Maintenance Summary',
            'booking-summary' => 'Booking Summary',
            'device-status' => 'Device Status',
            'alerts-history' => 'Alerts History',
            'ticket-summary' => 'Ticket Summary',
        ];
    }

    public static function intervals(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ];
    }

    public static function daysOfWeek(): array
    {
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    }

    public static function timeOptions(): array
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            for ($m = 0; $m < 60; $m += 30) {
                $times[] = sprintf('%02d:%02d', $h, $m);
            }
        }

        return $times;
    }

    public function reportTypeLabel(): string
    {
        return self::reportTypes()[$this->report_type] ?? $this->report_type;
    }

    public function intervalLabel(): string
    {
        $label = self::intervals()[$this->interval] ?? $this->interval;

        if ($this->interval === 'weekly' && $this->day_of_week) {
            $label .= ' ('.ucfirst($this->day_of_week).')';
        } elseif ($this->interval === 'monthly' && $this->day_of_month) {
            $label .= ' (day '.$this->day_of_month.')';
        }

        return $label;
    }
}
