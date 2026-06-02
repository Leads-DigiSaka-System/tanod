<?php

namespace App\Services\Jimi;

use App\Models\Device;
use App\Models\DeviceTrackRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Handles track/mileage data from Jimi API and stores locally.
 */
class JimiTrackingService
{
    public function __construct(
        private JimiAuthService $auth,
    ) {}

    /**
     * Get the total machine hours across all devices from Jimi API.
     * Uses jimi.device.track.mileage with sliding 365-day windows.
     * Cached for 60 minutes to avoid excessive API calls.
     *
     * @param  bool  $forceRefresh  Bypass cache
     * @return float Total hours rounded to 2 decimals
     */
    public function getTotalMachineHours(bool $forceRefresh = false): float
    {
        $cacheKey = 'jimi_total_machine_hours';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return (float) Cache::remember($cacheKey, now()->addMinutes(60), function () {
            return $this->fetchTotalMachineHours();
        });
    }

    private function fetchTotalMachineHours(): float
    {
        $imeis = Device::where('is_active', true)->pluck('imei')->filter()->values()->toArray();

        if (empty($imeis)) {
            return 0;
        }

        $totalSeconds = 0;
        $batchSize = config('jimi.batch_size', 50);
        $batches = array_chunk($imeis, $batchSize);
        $windows = $this->buildDateWindows();

        foreach ($windows as $window) {
            foreach ($batches as $batch) {
                try {
                    $response = $this->auth->call('jimi.device.track.mileage', [
                        'imeis' => implode(',', $batch),
                        'begin_time' => $window['start'],
                        'end_time' => $window['end'],
                    ]);

                    $code = (int) ($response['code'] ?? -1);

                    // Daily API quota exceeded — stop early
                    if ($code === 1006) {
                        Log::warning('Jimi API quota exceeded while fetching total machine hours');
                        break 2;
                    }

                    foreach ($response['result'] ?? [] as $trip) {
                        $totalSeconds += (int) ($trip['runTimeSecond'] ?? 0);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch machine hours batch: '.$e->getMessage());
                }

                usleep(300000); // 300ms delay between batches
            }
        }

        return round($totalSeconds / 3600, 2);
    }

    /**
     * Build sliding 365-day date windows from 2023-01-01 to now.
     */
    private function buildDateWindows(): array
    {
        $windows = [];
        $cursor = Carbon::create(2023, 1, 1, 0, 0, 0);
        $now = now();

        while ($cursor->lt($now)) {
            $end = $cursor->copy()->addDays(365);
            if ($end->gt($now)) {
                $end = $now->copy();
            }

            $windows[] = [
                'start' => $cursor->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ];

            $cursor = $end->copy();
        }

        return $windows;
    }

    /**
     * Fetch track/mileage data for a device within a date range.
     * Uses: jimi.device.track.mileage (API 3.4)
     *
     * Stores each trip record in device_track_records.
     *
     * @param  string  $beginTime  Y-m-d H:i:s (GMT)
     * @param  string  $endTime  Y-m-d H:i:s (GMT)
     * @return array Raw API result
     */
    public function fetchTrackMileage(string $imei, string $beginTime, string $endTime): array
    {
        $response = $this->auth->call('jimi.device.track.mileage', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            Log::warning("Jimi track mileage failed for {$imei}", ['response' => $response]);

            return [];
        }

        $records = $response['result'] ?? [];
        $this->storeTrackRecords($imei, $records);

        return $records;
    }

    /**
     * Fetch track data (GPS points) for a device.
     * Uses: jimi.device.track.list (API 3.3)
     */
    public function fetchTrackData(string $imei, string $beginTime, string $endTime): array
    {
        $response = $this->auth->call('jimi.device.track.list', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'map_type' => config('jimi.map_type', 'WGS84'),
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            return [];
        }

        return $response['result'] ?? [];
    }

    /**
     * Fetch mileage for multiple IMEIs in batches and store.
     *
     * API returns per-trip records with:
     *   distance      – trip distance in meters
     *   endMileage    – cumulative odometer in meters
     *   runTimeSecond – trip runtime in seconds
     *
     * @return array IMEI => ['distance_km' => float, 'runtime_seconds' => int, 'odometer_km' => float]
     */
    public function fetchBatchMileage(array $imeis, string $beginTime, string $endTime): array
    {
        $batchSize = config('jimi.batch_size', 50);
        $results = [];

        foreach (array_chunk($imeis, $batchSize) as $chunk) {
            $imeiStr = implode(',', $chunk);

            try {
                $response = $this->auth->call('jimi.device.track.mileage', [
                    'imeis' => $imeiStr,
                    'begin_time' => $beginTime,
                    'end_time' => $endTime,
                ]);
            } catch (\Exception $e) {
                Log::warning('fetchBatchMileage: API error: '.$e->getMessage());

                continue;
            }

            if (((int) ($response['code'] ?? -1)) !== 0) {
                continue;
            }

            foreach ($response['result'] ?? [] as $record) {
                $recordImei = $record['imei'] ?? null;
                if (! $recordImei) {
                    continue;
                }

                if (! isset($results[$recordImei])) {
                    $results[$recordImei] = ['distance_km' => 0, 'runtime_seconds' => 0, 'odometer_km' => 0];
                }

                // distance is per-trip in meters
                $results[$recordImei]['distance_km'] += (float) ($record['distance'] ?? 0) / 1000;
                $results[$recordImei]['runtime_seconds'] += (int) ($record['runTimeSecond'] ?? 0);

                // endMileage is the cumulative odometer in meters — keep the highest
                $endMileage = (float) ($record['endMileage'] ?? 0) / 1000;
                if ($endMileage > $results[$recordImei]['odometer_km']) {
                    $results[$recordImei]['odometer_km'] = $endMileage;
                }

                $this->storeTrackRecords($recordImei, [$record]);
            }

            usleep(300000); // 300ms between batches
        }

        return $results;
    }

    /**
     * Persist track records into the database.
     */
    private function storeTrackRecords(string $imei, array $records): void
    {
        $device = Device::where('imei', $imei)->first();
        if (! $device) {
            return;
        }

        foreach ($records as $record) {
            DeviceTrackRecord::create([
                'device_id' => $device->id,
                'imei' => $imei,
                'start_lat' => $record['startLat'] ?? null,
                'start_lng' => $record['startLng'] ?? null,
                'end_lat' => $record['endLat'] ?? null,
                'end_lng' => $record['endLng'] ?? null,
                'mileage' => (float) ($record['distance'] ?? 0) / 1000,
                'run_time_seconds' => (int) ($record['runTimeSecond'] ?? 0),
                'max_speed' => (float) ($record['maxSpeed'] ?? 0),
                'start_time' => ! empty($record['startTime'])
                    ? Carbon::parse($record['startTime'])
                    : null,
                'end_time' => ! empty($record['endTime'])
                    ? Carbon::parse($record['endTime'])
                    : null,
                'raw_data' => $record,
            ]);
        }
    }

    /**
     * Get device sharing URL from Jimi.
     * Uses: jimi.device.sharing.location.url (API 3.5)
     */
    public function getSharingUrl(string $imei): ?string
    {
        $response = $this->auth->call('jimi.device.sharing.location.url', [
            'imei' => $imei,
        ]);

        return $response['result']['url'] ?? null;
    }

    /**
     * Get parking/idling report from Jimi.
     */
    public function getParkingIdlingReport(string $imei, string $beginTime, string $endTime): array
    {
        $response = $this->auth->call('jimi.open.device.parking.info', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);

        return $response['result'] ?? [];
    }
}
