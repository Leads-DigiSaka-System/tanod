<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertPurgeService;
use App\Services\AlertSummaryService;
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

        if ($this->option('dry-run')) {
            $count = Alert::query()->where('created_at', '<', $cutoff)->count();
            $this->info("Dry run: {$count} alert(s) older than {$cutoff->toDateTimeString()} would be deleted.");

            return self::SUCCESS;
        }

        $deleted = AlertPurgeService::purge($days, $chunkSize);

        AlertSummaryService::recalculate();

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
