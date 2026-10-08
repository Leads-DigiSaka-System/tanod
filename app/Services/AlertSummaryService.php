<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertSummary;

class AlertSummaryService
{
    /**
     * Get (or lazily create) the single summary row.
     */
    public static function row(): AlertSummary
    {
        return AlertSummary::firstOrCreate(['id' => 1], [
            'total_alerts' => 0,
            'unacknowledged_alerts' => 0,
            'by_type' => [],
        ]);
    }

    /**
     * Increment counts for a newly created alert.
     */
    public static function increment(Alert $alert): void
    {
        $summary = self::row();
        $byType = $summary->by_type ?? [];

        $type = $alert->type;
        $current = $byType[$type] ?? ['total' => 0, 'unacknowledged' => 0];

        $byType[$type] = [
            'total' => $current['total'] + 1,
            'unacknowledged' => $current['unacknowledged'] + ($alert->is_acknowledged ? 0 : 1),
        ];

        $summary->update([
            'total_alerts' => $summary->total_alerts + 1,
            'unacknowledged_alerts' => $summary->unacknowledged_alerts + ($alert->is_acknowledged ? 0 : 1),
            'by_type' => $byType,
        ]);
    }

    /**
     * Decrement counts when an alert is deleted.
     */
    public static function decrement(Alert $alert): void
    {
        $summary = self::row();
        $byType = $summary->by_type ?? [];

        $type = $alert->type;
        $current = $byType[$type] ?? ['total' => 0, 'unacknowledged' => 0];
        $unackDelta = $alert->is_acknowledged ? 0 : 1;

        $byType[$type] = [
            'total' => max(0, $current['total'] - 1),
            'unacknowledged' => max(0, $current['unacknowledged'] - $unackDelta),
        ];

        $summary->update([
            'total_alerts' => max(0, $summary->total_alerts - 1),
            'unacknowledged_alerts' => max(0, $summary->unacknowledged_alerts - $unackDelta),
            'by_type' => $byType,
        ]);
    }

    /**
     * Adjust unacknowledged counts when an alert's acknowledgement state changes.
     */
    public static function handleAcknowledgedChange(Alert $alert, bool $isAcknowledged): void
    {
        $summary = self::row();
        $byType = $summary->by_type ?? [];

        $type = $alert->type;
        $current = $byType[$type] ?? ['total' => 0, 'unacknowledged' => 0];

        // Acknowledged -> unacknowledged goes down; un-acknowledged -> up.
        $delta = $isAcknowledged ? -1 : 1;

        $byType[$type] = [
            'total' => $current['total'],
            'unacknowledged' => max(0, $current['unacknowledged'] + $delta),
        ];

        $summary->update([
            'unacknowledged_alerts' => max(0, $summary->unacknowledged_alerts + $delta),
            'by_type' => $byType,
        ]);
    }

    /**
     * Rebuild the summary from the alerts table (used after bulk operations).
     */
    public static function recalculate(): void
    {
        $counts = Alert::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_acknowledged = 0 then 1 else 0 end) as unacknowledged')
            ->first();

        $byType = Alert::query()
            ->select('type')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_acknowledged = 0 then 1 else 0 end) as unacknowledged')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->type => [
                    'total' => (int) $item->total,
                    'unacknowledged' => (int) $item->unacknowledged,
                ],
            ])
            ->all();

        AlertSummary::updateOrCreate(
            ['id' => 1],
            [
                'total_alerts' => (int) $counts->total,
                'unacknowledged_alerts' => (int) $counts->unacknowledged,
                'by_type' => $byType,
            ]
        );
    }
}
