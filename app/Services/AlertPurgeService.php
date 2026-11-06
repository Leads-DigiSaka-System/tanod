<?php

namespace App\Services;

use App\Models\Alert;

class AlertPurgeService
{
    /**
     * Permanently delete alerts older than the given number of days.
     *
     * @return int Number of alerts deleted.
     */
    public static function purge(int $days, int $chunk = 1000): int
    {
        $cutoff = now()->subDays($days);
        $query = Alert::query()->where('created_at', '<', $cutoff);

        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += Alert::query()->whereKey($ids)->delete();
        } while ($ids->count() === $chunk);

        return $deleted;
    }
}
