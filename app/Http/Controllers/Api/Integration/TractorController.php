<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationAlertIndexRequest;
use App\Http\Requests\IntegrationLocationHistoryRequest;
use App\Http\Requests\IntegrationMaintenanceIndexRequest;
use App\Http\Requests\IntegrationTrackDataRequest;
use App\Http\Requests\IntegrationTractorIndexRequest;
use App\Http\Resources\IntegrationAlertResource;
use App\Http\Resources\IntegrationLocationResource;
use App\Http\Resources\IntegrationMaintenanceResource;
use App\Http\Resources\IntegrationTrackRecordResource;
use App\Http\Resources\IntegrationTractorResource;
use App\Models\Alert;
use App\Models\DeviceTrackRecord;
use App\Models\Tractor;
use App\Services\Jimi\JimiTrackingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TractorController extends Controller
{
    public function index(IntegrationTractorIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $search = trim((string) ($validated['search'] ?? ''));

        $tractors = Tractor::query()
            ->with(['device.latestLocation', 'assignee', 'groups'])
            ->withSum('trackRecords', 'mileage')
            ->withSum('trackRecords', 'run_time_seconds')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('no_plate', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('engine_no', 'like', "%{$search}%")
                        ->orWhere('chassis_no', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationTractorResource::collection($tractors);
    }

    public function show(string $tractor): IntegrationTractorResource
    {
        $tractor = $this->resolveTractor($tractor);

        $tractor->load([
            'device.latestLocation',
            'assignee',
            'groups',
            'images',
        ]);
        $tractor->loadSum('trackRecords', 'mileage');
        $tractor->loadSum('trackRecords', 'run_time_seconds');

        return new IntegrationTractorResource($tractor);
    }

    public function location(string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);

        $tractor->load('device.latestLocation');
        $device = $tractor->device;
        $location = $device?->latestLocation;

        if (! $device || ! $location) {
            return response()->json([
                'message' => 'No tracking location is available for this tractor.',
                'data' => null,
            ], 404);
        }

        $recordedAt = $location->heartbeat_at ?? $location->created_at;
        $ageSeconds = $recordedAt ? (int) $recordedAt->diffInSeconds(now()) : null;
        $speed = (float) ($location->speed ?? 0);
        $ignitionOn = (bool) ($location->acc_status ?? false);

        // Status: Offline, Parked, Idle, Moving
        $status = 'Offline';
        if ($recordedAt && now()->diffInMinutes($recordedAt) <= 10) {
            if (! $ignitionOn) {
                $status = 'Parked';
            } elseif ($speed >= 3.0) {
                $status = 'Moving';
            } else {
                $status = 'Idle';
            }
        }

        return response()->json([
            'data' => [
                'tractor' => [
                    'id' => $tractor->id,
                    'name' => $tractor->name,
                    'plate_number' => $tractor->no_plate,
                ],
                'device' => [
                    'id' => $device->id,
                    'imei' => $device->imei,
                    'name' => $device->device_name,
                ],
                'position' => [
                    'latitude' => $location->lat,
                    'longitude' => $location->lng,
                    'speed_kph' => $location->speed,
                    'direction_degrees' => $location->direction,
                    'gps_satellites' => $location->gps_num,
                    'position_source' => $location->pos_type,
                ],
                'ignition_on' => $ignitionOn,
                'online' => $device->isOnline(),
                'recorded_at' => $recordedAt?->toIso8601String(),
                'age_seconds' => $ageSeconds,
                'stale' => $ageSeconds === null || $ageSeconds > 300,
                'Status' => $status,
                'Serial_no' => $tractor->id_no,
            ],
        ]);
    }

    public function alerts(IntegrationAlertIndexRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->safe()->except('tractor_id');

        $alerts = Alert::query()
            ->with(['tractor:id,name,no_plate', 'device:id,imei,device_name'])
            ->where(function (Builder $query) use ($tractor): void {
                $query->where('tractor_id', $tractor->id)
                    ->when($tractor->device_id, fn (Builder $deviceQuery): Builder => $deviceQuery->orWhere('device_id', $tractor->device_id));
            })
            ->when(isset($validated['type']), fn (Builder $query): Builder => $query->where('type', $validated['type']))
            ->when(array_key_exists('acknowledged', $validated), fn (Builder $query): Builder => $query->where('is_acknowledged', $request->boolean('acknowledged')))
            ->when(isset($validated['from']), fn (Builder $query): Builder => $query->where('created_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn (Builder $query): Builder => $query->where('created_at', '<=', $validated['to']))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationAlertResource::collection($alerts);
    }

    public function locationHistory(IntegrationLocationHistoryRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $locations = $device->locations()
            ->when(isset($validated['from']), fn ($query) => $query->where('heartbeat_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->where('heartbeat_at', '<=', $validated['to']))
            ->latest('heartbeat_at')
            ->paginate((int) ($validated['per_page'] ?? 100))
            ->withQueryString();

        IntegrationLocationResource::$serialNo = $tractor->id_no;

        return IntegrationLocationResource::collection($locations);
    }

    public function maintenance(IntegrationMaintenanceIndexRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();

        $maintenance = $tractor->maintenances()
            ->with(['issueType:id,name', 'performer:id,name', 'images'])
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['from']), fn ($query) => $query->where('maintenance_date', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->where('maintenance_date', '<=', $validated['to']))
            ->latest('maintenance_date')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationMaintenanceResource::collection($maintenance);
    }

    public function mileage(IntegrationTrackDataRequest $request, string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->safe()->except(['per_page', 'page']);
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $trackRecords = $this->trackRecordsForRange(
            DeviceTrackRecord::query()->where('device_id', $device->id),
            $validated,
        );
        $distanceKilometers = round((float) (clone $trackRecords)->sum('mileage'), 2);
        $runtimeSeconds = (int) (clone $trackRecords)->sum('run_time_seconds');
        $tripCount = (clone $trackRecords)->count();
        $maximumSpeed = round((float) ((clone $trackRecords)->max('max_speed') ?? 0), 2);
        $allTimeStoredDistance = (float) $device->trackRecords()->sum('mileage');
        $allTimeRuntimeSeconds = (int) $device->trackRecords()->sum('run_time_seconds');

        $daily = (clone $trackRecords)
            ->whereNotNull('start_time')
            ->selectRaw('DATE(start_time) as date')
            ->selectRaw('ROUND(SUM(mileage), 2) as mileage_km')
            ->selectRaw('SUM(run_time_seconds) as runtime_seconds')
            ->selectRaw('MAX(max_speed) as maximum_speed_kph')
            ->selectRaw('COUNT(*) as trips')
            ->groupByRaw('DATE(start_time)')
            ->orderBy('date')
            ->get()
            ->map(fn ($day): array => [
                'date' => $day->date,
                'mileage_km' => (float) $day->mileage_km,
                'runtime_seconds' => (int) $day->runtime_seconds,
                'runtime_hours' => round((int) $day->runtime_seconds / 3600, 2),
                'maximum_speed_kph' => (float) $day->maximum_speed_kph,
                'trips' => (int) $day->trips,
            ]);

        return response()->json([
            'data' => [
                'tractor' => [
                    'id' => $tractor->id,
                    'name' => $tractor->name,
                    'plate_number' => $tractor->no_plate,
                ],
                'device' => [
                    'id' => $device->id,
                    'imei' => $device->imei,
                ],
                'range' => [
                    'from' => $validated['from'] ?? null,
                    'to' => $validated['to'] ?? null,
                ],
                'summary' => [
                    'mileage_km' => $distanceKilometers,
                    'runtime_seconds' => $runtimeSeconds,
                    'runtime_hours' => round($runtimeSeconds / 3600, 2),
                    'maximum_speed_kph' => $maximumSpeed,
                    'average_mileage_per_trip_km' => $tripCount > 0 ? round($distanceKilometers / $tripCount, 2) : 0,
                    'trips' => $tripCount,
                ],
                'all_time' => [
                    'odometer_km' => round(max((float) $tractor->total_distance, $allTimeStoredDistance), 2),
                    'running_hours' => round(max((float) $tractor->running_hours, $allTimeRuntimeSeconds / 3600), 2),
                ],
                'daily' => $daily,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function withinBoundaries(Request $request, string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $tractor->load('device.latestLocation');
        $location = $tractor->device?->latestLocation;

        if (! $location) {
            return response()->json([
                'data' => [
                    'Within_Boundaries' => false,
                    'Current_Longitude' => null,
                    'Current_Latitude' => null,
                ],
            ]);
        }

        $province = $request->input('province', '');
        $withinBoundaries = false;
        $debug = [];

        if ($province) {
            $key = env('GOOGLE_MAP_KEY', config('services.google.maps_key'));
            $debug['key_found'] = !empty($key);
            $debug['key_prefix'] = $key ? substr($key, 0, 8) . '...' : 'empty';

            if ($key) {
                $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$location->lat},{$location->lng}&key={$key}&language=en";
                $debug['url'] = $url;

                try {
                    $raw = file_get_contents($url);
                    $debug['raw_length'] = strlen($raw);
                    $response = json_decode($raw, true);
                    $debug['api_status'] = $response['status'] ?? 'no status';
                    $debug['results_count'] = count($response['results'] ?? []);

                    if (($response['status'] ?? '') === 'OK') {
                        $provincesFound = [];
                        foreach ($response['results'] as $result) {
                            foreach ($result['address_components'] as $component) {
                                $types = $component['types'] ?? [];
                                // Philippines: level_1 = Region, level_2 = Province
                                if (array_intersect($types, ['administrative_area_level_1', 'administrative_area_level_2'])) {
                                    $provinceName = $component['long_name'];
                                    $provincesFound[] = $provinceName . ' (' . implode(',', $types) . ')';
                                    $withinBoundaries = strcasecmp(trim($provinceName), trim($province)) === 0
                                        || stripos($provinceName, $province) !== false
                                        || stripos($province, $provinceName) !== false;
                                    if ($withinBoundaries) {
                                        break 2;
                                    }
                                }
                            }
                        }
                        $debug['provinces_found'] = $provincesFound;
                    }

                    if (($response['status'] ?? '') !== 'OK') {
                        $debug['api_error'] = $response['error_message'] ?? $response['status'] ?? 'unknown';
                    }
                } catch (\Throwable $e) {
                    $debug['exception'] = $e->getMessage();
                }
            }
        }

        return response()->json([
            'data' => [
                'Within_Boundaries' => $withinBoundaries,
                'Current_Longitude' => $location->lng,
                'Current_Latitude' => $location->lat,
            ],
            '_debug' => $debug,
        ]);
    }

    public function events(IntegrationAlertIndexRequest $request, string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->safe()->except(['per_page', 'page']);

        $alerts = Alert::query()
            ->whereHas('device', fn ($q) => $q->whereHas('tractor', fn ($q) => $q->where('id', $tractor->id)))
            ->when(isset($validated['from']), fn ($q) => $q->where('created_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($q) => $q->where('created_at', '<=', $validated['to']))
            ->latest()
            ->get();

        $data = $alerts->map(fn ($alert) => [
            'Text' => $alert->message ?? $alert->title ?? '',
            'Type' => $alert->type ?? '',
            'Timestamp' => $alert->created_at?->toIso8601String(),
            'Received_Time' => $alert->resolved_at?->toIso8601String() ?? $alert->created_at?->toIso8601String(),
            'Serial_No' => $tractor->id_no ?? '—',
        ]);

        return response()->json(['data' => $data]);
    }

    public function maintenanceStatus(string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);

        $statusMap = [
            'due' => 'Due',
            'upcoming' => 'Due Soon',
            'ok' => 'Not Due',
        ];

        return response()->json([
            'data' => [
                'Status' => $statusMap[$tractor->pmsStatus()] ?? 'Not Due',
                'Serial_No' => $tractor->id_no ?? '—',
            ],
        ]);
    }

    public function utilization(IntegrationTrackDataRequest $request, string $tractor, JimiTrackingService $trackingService): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $from = $request->input('date_from', $request->input('from'));
        $to = $request->input('date_to', $request->input('to'));

        // Fetch from Jimi API (same as LiveView playback)
        $chunks = $this->dateChunks($from, $to, 1);
        $allPoints = [];

        foreach ($chunks as [$chunkStart, $chunkEnd]) {
            try {
                $points = $trackingService->fetchTrackData($device->imei, $chunkStart, $chunkEnd);
                $allPoints = array_merge($allPoints, $points);
            } catch (\Throwable $e) {}
        }

        $distanceKm = 0;
        $movingSeconds = 0;
        $idleSeconds = 0;

        if (! empty($allPoints)) {
            // Normalize and sort
            $normalized = [];
            foreach ($allPoints as $point) {
                $time = Carbon::parse($point['gpsTime'] ?? $point['positionTime'] ?? null);
                if (! $time) continue;
                $normalized[] = [
                    'time' => $time,
                    'speed' => max((float) ($point['speed'] ?? $point['gpsSpeed'] ?? 0), 0),
                    'lat' => (float) ($point['lat'] ?? 0),
                    'lng' => (float) ($point['lng'] ?? 0),
                ];
            }
            usort($normalized, fn ($a, $b) => $a['time']->timestamp <=> $b['time']->timestamp);

            $previous = null;
            $movingThreshold = 3.0;
            $totalDist = 0;

            foreach ($normalized as $point) {
                if ($previous) {
                    $elapsed = abs($previous['time']->diffInSeconds($point['time']));
                    $dist = $this->haversineKm($previous['lat'], $previous['lng'], $point['lat'], $point['lng']);

                    if ($elapsed <= 600 && $dist < 100) { // Skip gaps/outliers
                        $totalDist += $dist;

                        if (max($previous['speed'], $point['speed']) >= $movingThreshold) {
                            $movingSeconds += $elapsed;
                        } else {
                            $idleSeconds += $elapsed;
                        }
                    }
                }
                $previous = $point;
            }

            $distanceKm = round($totalDist, 2);
        }

        $workingHours = round($movingSeconds / 3600, 2);
        $engineHours = round(($movingSeconds + $idleSeconds) / 3600, 2);

        return response()->json([
            'data' => [
                'Working_Hours' => $workingHours,
                'Engine_Hours' => $engineHours,
                'Travel_Time_minutes' => (int) round($movingSeconds / 60),
                'Operating_Time_minutes' => (int) round(($movingSeconds + $idleSeconds) / 60),
                'Distance_km' => $distanceKm,
                'Serial_no' => $tractor->id_no ?? '—',
            ],
        ]);
    }

    public function statusSummary(IntegrationTrackDataRequest $request, string $tractor, JimiTrackingService $trackingService): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $from = $request->input('date_from', $request->input('from'));
        $to = $request->input('date_to', $request->input('to'));

        // Split into 1-day chunks for Jimi API reliability
        $chunks = $this->dateChunks($from, $to, 1);
        $allPoints = [];

        foreach ($chunks as [$chunkStart, $chunkEnd]) {
            try {
                $points = $trackingService->fetchTrackData($device->imei, $chunkStart, $chunkEnd);
                $allPoints = array_merge($allPoints, $points);
            } catch (\Throwable $e) {
                // Skip failed chunks
            }
        }

        if (empty($allPoints)) {
            return response()->json([
                'message' => 'No track data available for this period.',
                'data' => [
                    ['Status' => 'Moving',  'Time_minutes' => 0, 'Serial_no' => $tractor->id_no ?? '—'],
                    ['Status' => 'Idle',    'Time_minutes' => 0, 'Serial_no' => $tractor->id_no ?? '—'],
                    ['Status' => 'Parked',  'Time_minutes' => 0, 'Serial_no' => $tractor->id_no ?? '—'],
                    ['Status' => 'Offline', 'Time_minutes' => 0, 'Serial_no' => $tractor->id_no ?? '—'],
                ],
            ]);
        }

        // Normalize and sort all points by GPS time
        $normalized = [];
        foreach ($allPoints as $point) {
            $time = Carbon::parse($point['gpsTime'] ?? $point['positionTime'] ?? null);
            if (! $time) continue;

            $normalized[] = [
                'time' => $time,
                'speed' => max((float) ($point['speed'] ?? $point['gpsSpeed'] ?? 0), 0),
            ];
        }

        usort($normalized, fn ($a, $b) => $a['time']->timestamp <=> $b['time']->timestamp);

        $movingSeconds = 0;
        $idleSeconds = 0;
        $previous = null;
        $movingThreshold = 3.0;

        foreach ($normalized as $point) {
            if ($previous) {
                $elapsed = abs($previous['time']->diffInSeconds($point['time']));

                if (max($previous['speed'], $point['speed']) >= $movingThreshold) {
                    $movingSeconds += $elapsed;
                } else {
                    $idleSeconds += $elapsed;
                }
            }
            $previous = $point;
        }

        return response()->json([
            'data' => [
                ['Status' => 'Moving',  'Time_minutes' => (int) round($movingSeconds / 60),  'Serial_no' => $tractor->id_no ?? '—'],
                ['Status' => 'Idle',    'Time_minutes' => (int) round($idleSeconds / 60),    'Serial_no' => $tractor->id_no ?? '—'],
            ],
        ]);
    }

    public function trackData(IntegrationTrackDataRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $trackRecords = $this->trackRecordsForRange(
            DeviceTrackRecord::query()->where('device_id', $device->id),
            $validated,
        )
            ->with('device.tractor:id,device_id')
            ->latest('start_time')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationTrackRecordResource::collection($trackRecords);
    }

    /**
     * @param  array<string, mixed>  $range
     */
    private function trackRecordsForRange(Builder $query, array $range): Builder
    {
        return $query
            ->when(isset($range['from']), fn (Builder $trackQuery): Builder => $trackQuery
                ->where('start_time', '>=', Carbon::parse($range['from'])->startOfDay()))
            ->when(isset($range['to']), fn (Builder $trackQuery): Builder => $trackQuery
                ->where('start_time', '<=', Carbon::parse($range['to'])->endOfDay()));
    }

    /**
     * Split a date range into chunks (same as LiveView).
     * @return array<int, array{string, string}>
     */
    private function dateChunks(string $from, string $to, int $days = 2): array
    {
        $begin = Carbon::parse($from);
        $end = Carbon::parse($to);
        $chunks = [];

        while ($begin->lt($end)) {
            $chunkEnd = $begin->copy()->addDays($days);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }
            $chunks[] = [$begin->format('Y-m-d H:i:s'), $chunkEnd->format('Y-m-d H:i:s')];
            $begin = $chunkEnd;
        }

        return $chunks;
    }

    /**
     * Haversine distance in km between two lat/lng points.
     */
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function resolveTractor(string $identifier): Tractor
    {
        $tractor = Tractor::query()->where('id_no', $identifier)->first()
            ?? Tractor::query()->find($identifier)
            ?? Tractor::query()->where('imei', $identifier)->first();

        if ($tractor) {
            return $tractor;
        }

        $nameMatches = Tractor::query()
            ->where('name', $identifier)
            ->orderBy('id')
            ->limit(2)
            ->get();

        abort_if(
            $nameMatches->count() > 1,
            409,
            'Multiple tractors use this name. Use the tractor ID or IMEI instead.',
        );

        if ($nameMatches->isNotEmpty()) {
            return $nameMatches->first();
        }

        abort(404, 'Tractor not found. The identifier may have been deleted, renamed, or is incorrect.');
    }
}
