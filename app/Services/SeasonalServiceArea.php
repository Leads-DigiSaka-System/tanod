<?php

namespace App\Services;

use App\Models\Device;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeasonalServiceArea
{
    public function __construct(private TractorServiceAreaEstimator $estimator) {}

    /**
     * The most recently started dry season, based on the local crop calendar.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, today: CarbonImmutable, through: CarbonImmutable}
     */
    public function season(): array
    {
        $today = CarbonImmutable::today(config('jimi.display_timezone', 'Asia/Manila'));
        $start = $today->startOfYear()->addMonths(9);
        if ($today->lt($start)) {
            $start = $start->subYear();
        }

        $end = $start->addMonths(4)->subDay();

        return ['start' => $start, 'end' => $end, 'today' => $today, 'through' => $today->subDay()->min($end)];
    }

    public function trackedDevices(): Builder
    {
        return Device::query()->where('is_active', true)->whereHas('tractor')->notStale();
    }

    public function summary(): array
    {
        $season = $this->season();

        return Cache::remember($this->cacheKey($season), now()->addMinutes(3), fn () => $this->calculateSummary($season));
    }

    public function refreshSummary(): void
    {
        $season = $this->season();

        Cache::put($this->cacheKey($season), $this->calculateSummary($season), now()->addDay());
    }

    private function calculateSummary(array $season): array
    {
        $deviceQuery = $this->trackedDevices();
        $totalDevices = (clone $deviceQuery)->count();
        $elapsedDays = (int) $season['start']->diffInDays($season['through']->addDay());
        $checkedDays = 0;
        $observedDays = 0;
        $monthlyGps = [];
        $observedDevices = [];
        $firstDetected = [];

        if ($elapsedDays > 0 && $totalDevices > 0) {
            $days = DB::table('tractor_service_area_days')
                ->select(['id', 'device_id', 'local_date', 'has_usable_gps', 'cell_ids'])
                ->where('algorithm', $this->estimator->version())
                ->whereBetween('local_date', [$season['start']->toDateString(), $season['through']->toDateString()])
                ->whereIn('device_id', $deviceQuery->select('devices.id'))
                ->lazyById(1000);

            foreach ($days as $day) {
                $checkedDays++;
                if (! $day->has_usable_gps) {
                    continue;
                }

                $month = substr($day->local_date, 0, 7);
                $monthlyGps[$month] = true;
                $observedDevices[$day->device_id] = true;
                $observedDays++;

                foreach (json_decode($day->cell_ids, true, flags: JSON_THROW_ON_ERROR) as $cellId) {
                    $firstDetected[$cellId] = min($firstDetected[$cellId] ?? $month, $month);
                }
            }
        }

        $hectaresByMonth = array_count_values($firstDetected);
        $months = [];
        for ($index = 0; $index < 4; $index++) {
            $month = $season['start']->addMonths($index);
            $key = $month->format('Y-m');
            $months[] = [
                'key' => $key,
                'label' => $month->format('M Y'),
                'hectares' => isset($monthlyGps[$key])
                    ? ($hectaresByMonth[$key] ?? 0) * TractorServiceAreaEstimator::CELL_HECTARES
                    : null,
                'upcoming' => $month->gt($season['today']),
            ];
        }

        return [
            'startsOn' => $season['start']->toDateString(),
            'endsOn' => $season['end']->toDateString(),
            'label' => $season['start']->format('M Y').' – '.$season['end']->format('M Y'),
            'nextStartsOn' => $season['start']->addYear()->toDateString(),
            'closed' => $season['today']->gt($season['end']),
            'hectares' => $observedDays > 0 ? count($firstDetected) * TractorServiceAreaEstimator::CELL_HECTARES : null,
            'cellHectares' => TractorServiceAreaEstimator::CELL_HECTARES,
            'tractorsWithGps' => count($observedDevices),
            'checkedTractorDays' => $checkedDays,
            'expectedTractorDays' => $totalDevices * $elapsedDays,
            'months' => $months,
        ];
    }

    private function cacheKey(array $season): string
    {
        return 'season_service_area_'.$season['start']->toDateString().'_'.$season['through']->toDateString().'_'.$this->estimator->version();
    }
}
