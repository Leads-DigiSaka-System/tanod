<?php

namespace App\Console\Commands;

use App\Services\Jimi\JimiTrackingService;
use App\Services\SeasonalServiceArea;
use App\Services\TractorServiceAreaEstimator;
use App\Services\TractorTrackFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncTractorServiceArea extends Command
{
    protected $signature = 'jimi:sync-service-area {--limit=6 : Maximum track history requests in this batch}';

    protected $description = 'Process completed tractor GPS days for the dry-season service area estimate';

    private const WINDOW_DAYS = 7;

    private const ACCOUNT_BACKOFF = 'jimi_service_area_account_backoff';

    public function handle(
        SeasonalServiceArea $seasonal,
        JimiTrackingService $tracking,
        TractorTrackFormatter $formatter,
        TractorServiceAreaEstimator $estimator,
    ): int {
        if (Cache::has(self::ACCOUNT_BACKOFF)) {
            return self::SUCCESS;
        }

        $lock = Cache::lock('jimi_service_area_sync', 600);
        if (! $lock->get()) {
            return self::SUCCESS;
        }

        $limit = min(8, max(1, (int) $this->option('limit')));
        $season = $seasonal->season();
        $version = $estimator->version();
        $checked = 0;
        $observed = 0;
        $attempts = 0;

        try {
            foreach ($this->windows($season) as [$from, $through]) {
                $windowDays = (int) $from->diffInDays($through) + 1;
                $isOpenWindow = $through->eq($season['today']->subDay()) && $windowDays < self::WINDOW_DAYS
                    && $through->lt($season['end']);
                $requiredDays = $isOpenWindow ? max(1, $windowDays - 2) : $windowDays;

                $completed = DB::table('tractor_service_area_days')
                    ->select('device_id')
                    ->where('algorithm', $version)
                    ->whereBetween('local_date', [$from->toDateString(), $through->toDateString()])
                    ->groupBy('device_id')
                    ->havingRaw('COUNT(*) >= ?', [$requiredDays]);

                $devices = $seasonal->trackedDevices()
                    ->whereNotIn('devices.id', $completed)
                    ->select(['devices.id', 'devices.imei'])
                    ->lazyById(100, 'devices.id', 'id');

                foreach ($devices as $device) {
                    $retryKey = 'service_area_retry_'.$device->id.'_'.$from->toDateString();
                    if (Cache::has($retryKey)) {
                        continue;
                    }

                    $attempts++;
                    try {
                        $raw = $tracking->fetchTrackData(
                            $device->imei,
                            $from->utc()->toDateTimeString(),
                            $through->endOfDay()->utc()->toDateTimeString(),
                        );
                    } catch (\RuntimeException $exception) {
                        if ($exception->getCode() === JimiTrackingService::TRACK_QUOTA_CODE) {
                            Cache::put(self::ACCOUNT_BACKOFF, true, now()->addMinutes(30));
                            $this->warn('TrackSolid history quota reached. Service area processing will resume after the pause.');

                            break 2;
                        }

                        Cache::put($retryKey, true, now()->addHours(2));

                        if ($attempts >= $limit) {
                            break 2;
                        }

                        continue;
                    }

                    $rows = $this->dailyRows($device->id, $from, $through, $formatter->format($raw)['points'], $estimator, $version);
                    DB::table('tractor_service_area_days')->upsert($rows, ['device_id', 'local_date'], ['algorithm', 'has_usable_gps', 'cell_ids']);

                    $checked += count($rows);
                    $observed += array_sum(array_column($rows, 'has_usable_gps'));

                    if ($attempts >= $limit) {
                        break 2;
                    }
                }
            }
            if ($checked > 0) {
                $seasonal->refreshSummary();
            }
        } finally {
            $lock->release();
        }

        if ($attempts > 0) {
            $this->info("Seasonal service area: {$checked} tractor-days checked, {$observed} with usable GPS.");
        }

        return self::SUCCESS;
    }

    /** Work backward in calendar windows of at most seven completed days. */
    private function windows(array $season): array
    {
        // A whole local day must fall within TrackSolid's three-month history window.
        $retainedFrom = $season['today']->subMonthsNoOverflow(3)->addDay()->max($season['start']);
        $windows = [];

        for ($start = $season['start']; $start->lte($season['through']); $start = $start->addDays(self::WINDOW_DAYS)) {
            $from = $start->max($retainedFrom);
            $through = $start->addDays(self::WINDOW_DAYS - 1)->min($season['through']);

            if ($from->lte($through)) {
                $windows[] = [$from, $through];
            }
        }

        return array_reverse($windows);
    }

    private function dailyRows(
        int $deviceId,
        CarbonImmutable $from,
        CarbonImmutable $through,
        array $points,
        TractorServiceAreaEstimator $estimator,
        string $version,
    ): array {
        $perDay = [];
        $firstDay = $from->toDateString();
        $lastDay = $through->toDateString();
        $timezone = $from->timezone;

        foreach ($points as $point) {
            $date = CarbonImmutable::parse($point['gpsTime'])->setTimezone($timezone)->toDateString();
            if ($date >= $firstDay && $date <= $lastDay) {
                $perDay[$date][] = $point;
            }
        }

        $rows = [];
        for ($day = $from; $day->lte($through); $day = $day->addDay()) {
            $date = $day->toDateString();
            $result = $estimator->detectCells($perDay[$date] ?? []);
            $rows[] = [
                'device_id' => $deviceId,
                'local_date' => $date,
                'algorithm' => $version,
                'has_usable_gps' => $result['available'],
                'cell_ids' => json_encode($result['cellIds'], JSON_THROW_ON_ERROR),
            ];
        }

        return $rows;
    }
}
