<?php

namespace App\Console\Commands;

use App\Models\Alert;
use Illuminate\Console\Command;

class PurgeOldAlerts extends Command
{
    protected $signature = 'alerts:purge
                            {--days=30 : Delete alerts older than this many days}
                            {--chunk=1000 : Number of alerts to delete per batch}
                            {--dry-run : Show how many alerts would be deleted}';

    protected $description = 'Delete alerts older than the configured retention period';

    public function handle(): int
    {
        $days = $this->positiveIntegerOption('days');
        $chunkSize = $this->positiveIntegerOption('chunk');

        if ($days === null || $chunkSize === null) {
            return self::INVALID;
        }

        $cutoff = now()->subDays($days);
        $query = Alert::query()->where('created_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $count = (clone $query)->count();
            $this->info("Dry run: {$count} alert(s) older than {$cutoff->toDateTimeString()} would be deleted.");

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += Alert::query()->whereKey($ids)->delete();
        } while ($ids->count() === $chunkSize);

        $this->info("Deleted {$deleted} alert(s) older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }

    private function positiveIntegerOption(string $option): ?int
    {
        $value = (string) $this->option($option);

        if (! ctype_digit($value) || (int) $value < 1) {
            $this->error("The --{$option} option must be a positive integer.");

            return null;
        }

        return (int) $value;
    }
}
