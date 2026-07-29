<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceShare;
use App\Models\TractorGroup;
use App\Services\Jimi\JimiDeviceService;
use App\Services\Jimi\JimiTrackingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LiveViewController extends Controller
{
    /** Speed in km/h below which an online device is considered parked (filters GPS drift). */
    private const MOVING_SPEED_THRESHOLD = 3.0;

    public function __construct()
    {
        $this->middleware('permission:live_view.view');
    }

    public function index(Request $request)
    {
        $groups = TractorGroup::query()
            ->where('is_active', true)
            ->when($request->user()->hasRole('tps'), fn (Builder $query) => $query
                ->whereHas('users', fn (Builder $groupQuery) => $groupQuery->where('users.id', $request->user()->id)))
            ->get(['id', 'name']);

        return Inertia::render('LiveView/Index', [
            'devices' => [],
            'groups' => $groups,
            'googleMapKey' => config('services.google.maps_key', env('GOOGLE_MAP_KEY', '')),
        ]);
    }

    /**
     * Fetch GPS track data from JIMI API with period-based date ranges.
     * Supports chunking for long periods (JIMI max ~2 days per request).
     */
    public function getTrackData(Request $request, JimiTrackingService $trackingService)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'period' => 'required|in:today,yesterday,3days,week,month,custom',
            'from' => 'nullable|date|required_if:period,custom',
            'to' => 'nullable|date|required_if:period,custom|after_or_equal:from',
        ]);

        $device = $this->findAccessibleDevice($request, (int) $request->device_id);
        $imei = $device->imei;

        // Calculate date range based on period
        [$beginTime, $endTime] = $this->calculateDateRange(
            $request->period,
            $request->from,
            $request->to
        );

        // Cache completed history longer than a live "Today" range.
        $cacheKey = "track_data_v2_{$imei}_{$beginTime}_{$endTime}";
        $cacheSeconds = $request->period === 'today' ? 60 : 300;

        $trackResult = Cache::get($cacheKey);

        if ($trackResult === null) {
            // Split into 2-day chunks for JIMI API reliability
            $chunks = $this->getDateChunks($beginTime, $endTime, 2);
            $allPoints = [];
            $warnings = [];

            foreach ($chunks as [$chunkStart, $chunkEnd]) {
                try {
                    $points = $trackingService->fetchTrackData($imei, $chunkStart, $chunkEnd);
                    $allPoints = array_merge($allPoints, $points);
                } catch (\Throwable $exception) {
                    report($exception);
                    $warnings[] = [
                        'from' => $chunkStart,
                        'to' => $chunkEnd,
                        'message' => $exception->getMessage(),
                    ];
                }
            }

            $trackResult = ['points' => $allPoints, 'warnings' => $warnings];

            if ($warnings === []) {
                Cache::put($cacheKey, $trackResult, $cacheSeconds);
            }
        }

        // Process and return formatted track data
        $formatted = $this->formatTrackPoints($trackResult['points']);
        $warnings = $trackResult['warnings'];

        return response()->json([
            'success' => $formatted['totalPoints'] > 0 || $warnings === [],
            'partial' => $warnings !== [] && $formatted['totalPoints'] > 0,
            'warnings' => $warnings,
            'device' => [
                'id' => $device->id,
                'imei' => $device->imei,
                'device_name' => $device->device_name,
                'no_plate' => $device->tractor?->no_plate,
            ],
            'period' => $request->period,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'timezone' => config('jimi.display_timezone', 'Asia/Manila'),
            'track' => $formatted,
        ]);
    }

    /**
     * Real-time single-device location for the "Follow" feature.
     * Uses the shared 10-second JIMI cache and does not persist to the DB.
     */
    public function followDevice(Request $request, Device $device, JimiDeviceService $jimiService)
    {
        $device = $this->findAccessibleDevice($request, $device->id);
        $item = $jimiService->fetchDeviceLocationRealtime($device->imei);

        if ($item === null) {
            return response()->json([
                'message' => 'Live location is temporarily unavailable.',
            ], 503);
        }

        $device->load(['tractor.groups', 'tractor.assignee']);

        return response()->json([
            'device' => $this->formatDeviceFromApi($device, $item, true),
        ]);
    }

    /**
     * Legacy track endpoint – returns 2-hour trail from local DB.
     */
    public function trackDevice(Request $request, Device $device)
    {
        $device = $this->findAccessibleDevice($request, $device->id);
        $device->load(['latestLocation', 'tractor.groups', 'tractor.assignee']);

        $trail = DeviceLocation::where('device_id', $device->id)
            ->where('heartbeat_at', '>=', now()->subHours(2))
            ->orderBy('heartbeat_at')
            ->get(['lat', 'lng', 'speed', 'direction', 'heartbeat_at']);

        return response()->json([
            'device' => $device,
            'trail' => $trail,
        ]);
    }

    public function allLocations(JimiDeviceService $jimiService)
    {
        $locations = $jimiService->fetchLiveLocations();

        if ($locations === []) {
            return response()->json([
                'message' => 'Live locations are temporarily unavailable.',
            ], 503);
        }

        $devices = $this->accessibleDevicesQuery(request())
            ->select(['id', 'imei', 'device_name'])
            ->with([
                'tractor:id,device_id,no_plate,brand,model',
                'tractor.groups:id,name',
            ])
            ->where('is_active', true)
            ->get()
            ->map(fn ($device) => $this->formatDeviceFromApi($device, $locations[$device->imei] ?? null, false));

        return response()->json(['devices' => $devices]);
    }

    /**
     * Create a temporary share link for a device's live location.
     */
    public function createShare(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'duration' => 'nullable|integer|min:1|max:72', // hours
        ]);

        $device = $this->accessibleDevicesQuery($request)
            ->with('tractor')
            ->findOrFail($request->device_id);
        $duration = $request->input('duration', 1); // default 1 hour
        $token = Str::random(48);

        $share = DeviceShare::create([
            'token' => $token,
            'device_id' => $device->id,
            'imei' => $device->imei,
            'device_name' => $device->tractor?->no_plate ?? $device->device_name ?? $device->imei,
            'created_by' => auth()->id(),
            'expires_at' => now()->addHours($duration),
        ]);

        return response()->json([
            'success' => true,
            'url' => url("/share/{$token}"),
            'token' => $token,
            'expires' => $share->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Public share page – standalone Blade view (no auth required).
     */
    public function showShare(string $token, JimiDeviceService $jimiService)
    {
        $share = DeviceShare::where('token', $token)->first();

        if (! $share || $share->isExpired()) {
            return view('share.expired');
        }

        $device = Device::with(['tractor.groups', 'tractor.assignee'])->find($share->device_id);
        $locations = $jimiService->fetchLiveLocations();

        return view('share.show', [
            'share' => $share,
            'device' => $device ? $this->formatDeviceFromApi($device, $locations[$device->imei] ?? null, true) : null,
            'googleMapKey' => config('services.google.maps_key', env('GOOGLE_MAP_KEY', '')),
        ]);
    }

    /**
     * Public JSON endpoint for share auto-refresh (no auth).
     */
    public function shareData(string $token, JimiDeviceService $jimiService)
    {
        $share = DeviceShare::where('token', $token)->first();

        if (! $share || $share->isExpired()) {
            return response()->json(['expired' => true], 410);
        }

        $device = Device::with(['tractor.groups', 'tractor.assignee'])->find($share->device_id);
        $locations = $jimiService->fetchLiveLocations();

        return response()->json([
            'expired' => false,
            'device' => $device ? $this->formatDeviceFromApi($device, $locations[$device->imei] ?? null, true) : null,
            'expires' => $share->expires_at->toIso8601String(),
        ]);
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    /**
     * Format a device into a consistent array.
     */
    private function formatDevice(Device $device, bool $full = false): array
    {
        $loc = $device->latestLocation;
        $heartbeatAt = $loc?->heartbeat_at?->copy()?->utc();
        $minutesAgo = $heartbeatAt
            ? (int) floor($heartbeatAt->diffInMinutes(now()->utc()))
            : 999;
        $gpsAt = $this->parseHeartbeat(data_get($loc?->raw_data, 'gpsTime'));
        $gpsMinutesAgo = $gpsAt
            ? max((int) floor($gpsAt->diffInMinutes(now()->utc())), 0)
            : null;

        $status = $loc
            ? $this->resolveDeviceStatus(
                (int) $loc->status,
                $minutesAgo,
                (float) ($loc->speed ?? 0),
                (bool) $loc->acc_status,
                $gpsMinutesAgo ?? $minutesAgo,
            )
            : 'offline';

        $base = [
            'id' => $device->id,
            'imei' => $device->imei,
            'device_name' => $device->device_name,
            'status' => $status,
            'minutes_ago' => $minutesAgo,
            'lat' => $loc->lat ?? null,
            'lng' => $loc->lng ?? null,
            'speed' => $loc->speed ?? 0,
            'direction' => $loc->direction ?? 0,
            'acc_status' => $loc->acc_status ?? false,
            'heartbeat_at' => $heartbeatAt?->toIso8601String(),
            'gps_time' => $gpsAt?->toIso8601String(),
            'gps_minutes_ago' => $gpsMinutesAgo,
        ];

        if ($full) {
            $base['pos_type'] = $loc->pos_type ?? null;
            $base['gps_num'] = $loc->gps_num ?? null;
            $base['mileage'] = $loc->mileage ?? null;
            $base['tractor'] = $device->tractor ? [
                'id' => $device->tractor->id,
                'id_no' => $device->tractor->id_no,
                'no_plate' => $device->tractor->no_plate,
                'brand' => $device->tractor->brand,
                'model' => $device->tractor->model,
                'group' => $device->tractor->groups->first()?->name,
                'group_id' => $device->tractor->groups->first()?->id,
                'assignee' => $device->tractor->assignee?->name,
            ] : null;
        } else {
            $base['tractor'] = $device->tractor ? [
                'no_plate' => $device->tractor->no_plate,
                'brand' => $device->tractor->brand,
                'model' => $device->tractor->model,
                'group' => $device->tractor->groups->first()?->name,
                'group_id' => $device->tractor->groups->first()?->id,
            ] : null;
        }

        return $base;
    }

    /**
     * Format a device using real-time JIMI API data (no DB dependency).
     *
     * @param  array<string, mixed>|null  $apiData  Raw JIMI location item
     */
    private function formatDeviceFromApi(Device $device, ?array $apiData, bool $full = false): array
    {
        $minutesAgo = 999;
        $status = 'offline';
        $lat = null;
        $lng = null;
        $speed = 0;
        $direction = 0;
        $accStatus = false;
        $heartbeatAt = null;
        $gpsTime = null;
        $gpsMinutesAgo = null;
        $posType = null;
        $gpsNum = null;
        $mileage = null;

        if ($apiData) {
            $parsedHeartbeatAt = $this->parseHeartbeat($apiData['hbTime'] ?? null);
            $heartbeatAt = $parsedHeartbeatAt?->toIso8601String();
            $minutesAgo = $heartbeatAt
                ? (int) floor($parsedHeartbeatAt->diffInMinutes(now()->utc()))
                : 999;
            $parsedGpsAt = $this->parseHeartbeat($apiData['gpsTime'] ?? null);
            $gpsTime = $parsedGpsAt?->toIso8601String();
            $gpsMinutesAgo = $parsedGpsAt
                ? max((int) floor($parsedGpsAt->diffInMinutes(now()->utc())), 0)
                : null;

            $lat = $apiData['lat'] ?? null;
            $lng = $apiData['lng'] ?? null;
            $speed = (float) ($apiData['speed'] ?? 0);
            $direction = (float) ($apiData['direction'] ?? 0);
            $accStatus = (bool) ($apiData['accStatus'] ?? false);
            $posType = $apiData['posType'] ?? null;
            $gpsNum = (int) ($apiData['gpsNum'] ?? 0);
            $mileage = $apiData['mileage'] ?? null;

            // Prefer JIMI's own online/offline determination (status=1 → online).
            // Fall back to heartbeat-age threshold only when the status field
            // is missing from the API response, which can happen with older devices.
            $jimiStatus = array_key_exists('status', $apiData) ? (int) $apiData['status'] : null;

            $status = $this->resolveDeviceStatus(
                $jimiStatus,
                $minutesAgo,
                $speed,
                $accStatus,
                $gpsMinutesAgo ?? $minutesAgo,
            );
        }

        $base = [
            'id' => $device->id,
            'imei' => $device->imei,
            'device_name' => $device->device_name,
            'status' => $status,
            'minutes_ago' => $minutesAgo,
            'lat' => $lat,
            'lng' => $lng,
            'speed' => $speed,
            'direction' => $direction,
            'acc_status' => $accStatus,
            'heartbeat_at' => $heartbeatAt,
            'gps_time' => $gpsTime,
            'gps_minutes_ago' => $gpsMinutesAgo,
        ];

        if ($full) {
            $base['pos_type'] = $posType;
            $base['gps_num'] = $gpsNum;
            $base['mileage'] = $mileage;
            $base['tractor'] = $device->tractor ? [
                'id' => $device->tractor->id,
                'id_no' => $device->tractor->id_no,
                'no_plate' => $device->tractor->no_plate,
                'brand' => $device->tractor->brand,
                'model' => $device->tractor->model,
                'group' => $device->tractor->groups->first()?->name,
                'group_id' => $device->tractor->groups->first()?->id,
                'assignee' => $device->tractor->assignee?->name,
            ] : null;
        } else {
            $base['tractor'] = $device->tractor ? [
                'no_plate' => $device->tractor->no_plate,
                'brand' => $device->tractor->brand,
                'model' => $device->tractor->model,
                'group' => $device->tractor->groups->first()?->name,
                'group_id' => $device->tractor->groups->first()?->id,
            ] : null;
        }

        return $base;
    }

    private function parseHeartbeat(?string $heartbeatAt): ?Carbon
    {
        if (blank($heartbeatAt)) {
            return null;
        }

        try {
            return Carbon::parse($heartbeatAt, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function onlineThresholdMinutes(): int
    {
        return max((int) config('jimi.online_threshold_minutes', 10), 1);
    }

    private function movementFreshnessMinutes(): int
    {
        return max((int) config('jimi.movement_freshness_minutes', 5), 1);
    }

    private function resolveDeviceStatus(
        ?int $jimiStatus,
        int $minutesAgo,
        float $speed,
        bool $accStatus,
        int $gpsMinutesAgo,
    ): string {
        $isOnline = match ($jimiStatus) {
            1 => true,
            0 => false,
            default => $minutesAgo <= $this->onlineThresholdMinutes(),
        };

        if (! $isOnline) {
            return 'offline';
        }

        if (! $accStatus) {
            return 'parked';
        }

        $hasFreshMovement = $speed >= self::MOVING_SPEED_THRESHOLD
            && $gpsMinutesAgo <= $this->movementFreshnessMinutes();

        return $hasFreshMovement ? 'moving' : 'idling';
    }

    private function accessibleDevicesQuery(Request $request): Builder
    {
        $user = $request->user();

        return Device::query()
            ->whereHas('tractor', fn (Builder $query) => $this->scopeTractorsByRole($query, $user))
            ->notStale();
    }

    private function findAccessibleDevice(Request $request, int $deviceId): Device
    {
        return $this->accessibleDevicesQuery($request)->findOrFail($deviceId);
    }

    private function scopeTractorsByRole(Builder $query, \App\Models\User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return;
        }

        if ($user->hasRole('tps')) {
            $query->whereHas('groups.users', fn (Builder $groupQuery) => $groupQuery->where('users.id', $user->id));

            return;
        }

        $query->whereRaw('0 = 1');
    }

    /**
     * Calculate begin/end times for a given period.
     */
    private function calculateDateRange(string $period, ?string $from, ?string $to): array
    {
        $tz = config('jimi.display_timezone', 'Asia/Manila');

        switch ($period) {
            case 'today':
                $begin = Carbon::now($tz)->startOfDay();
                $end = Carbon::now($tz);
                break;
            case 'yesterday':
                $begin = Carbon::now($tz)->subDay()->startOfDay();
                $end = Carbon::now($tz)->subDay()->endOfDay();
                break;
            case '3days':
                $begin = Carbon::now($tz)->subDays(3)->startOfDay();
                $end = Carbon::now($tz);
                break;
            case 'week':
                $begin = Carbon::now($tz)->startOfWeek();
                $end = Carbon::now($tz);
                break;
            case 'month':
                $begin = Carbon::now($tz)->startOfMonth();
                $end = Carbon::now($tz);
                break;
            case 'custom':
                $begin = Carbon::parse($from, $tz)->startOfDay();
                $end = Carbon::parse($to, $tz)->endOfDay();
                break;
            default:
                $begin = Carbon::now($tz)->subDays(3)->startOfDay();
                $end = Carbon::now($tz);
        }

        return [
            $begin->utc()->format('Y-m-d H:i:s'),
            $end->utc()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Split a date range into chunks of N days.
     */
    private function getDateChunks(string $beginTime, string $endTime, int $days = 2): array
    {
        $begin = Carbon::parse($beginTime);
        $end = Carbon::parse($endTime);
        $chunks = [];

        while ($begin->lt($end)) {
            $chunkEnd = $begin->copy()->addDays($days);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }
            $chunks[] = [
                $begin->format('Y-m-d H:i:s'),
                $chunkEnd->format('Y-m-d H:i:s'),
            ];
            $begin = $chunkEnd;
        }

        return $chunks;
    }

    /**
     * Format raw JIMI track points into structured data.
     */
    private function formatTrackPoints(array $points): array
    {
        if (empty($points)) {
            return $this->emptyTrackSummary();
        }

        $rawPointCount = count($points);
        $normalized = [];
        $invalidPointCount = 0;

        foreach ($points as $point) {
            $lat = filter_var($point['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($point['lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $time = $this->parseTrackTimestamp($point['gpsTime'] ?? $point['positionTime'] ?? null);

            if ($lat === false || $lng === false || ! $this->isValidTrackCoordinate($lat, $lng) || $time === null) {
                $invalidPointCount++;

                continue;
            }

            $speed = max((float) ($point['speed'] ?? $point['gpsSpeed'] ?? 0), 0);
            $direction = fmod((float) ($point['course'] ?? $point['direction'] ?? 0), 360);

            $normalized[] = [
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $speed,
                'direction' => $direction < 0 ? $direction + 360 : $direction,
                'gpsTime' => $time->toIso8601String(),
                '_timestamp' => $time->getTimestamp(),
            ];
        }

        usort($normalized, fn (array $left, array $right): int => $left['_timestamp'] <=> $right['_timestamp']);

        $formatted = [];
        $speeds = [];
        $maxSpeed = 0;
        $totalDist = 0;
        $duplicatePointCount = 0;
        $outlierPointCount = 0;
        $segment = 0;
        $gaps = [];
        $seen = [];
        $previous = null;
        $movingDuration = 0;
        $idleDuration = 0;
        $idleRunDuration = 0;
        $stopCount = 0;
        $gapSeconds = max((int) config('jimi.track_gap_minutes', 10), 1) * 60;
        $maxPlausibleSpeed = max((float) config('jimi.track_max_plausible_speed_kph', 120), 1);
        $stopSeconds = max((int) config('jimi.track_stop_minutes', 5), 1) * 60;

        foreach ($normalized as $point) {
            $dedupKey = $point['gpsTime'].'|'.round($point['lat'], 6).'|'.round($point['lng'], 6);

            if (isset($seen[$dedupKey])) {
                $duplicatePointCount++;

                continue;
            }

            $seen[$dedupKey] = true;

            if ($previous !== null) {
                $elapsedSeconds = $point['_timestamp'] - $previous['_timestamp'];
                $distance = $this->haversineDistance(
                    $previous['lat'],
                    $previous['lng'],
                    $point['lat'],
                    $point['lng'],
                );
                $impliedSpeed = $elapsedSeconds > 0 ? $distance / ($elapsedSeconds / 3600) : INF;
                $gapReason = null;

                if ($elapsedSeconds > $gapSeconds) {
                    $gapReason = 'time_gap';
                } elseif ($elapsedSeconds <= 0 || $impliedSpeed > $maxPlausibleSpeed) {
                    $outlierPointCount++;
                    $gaps[] = [
                        'reason' => 'implausible_jump',
                        'fromTime' => $previous['gpsTime'],
                        'toTime' => $point['gpsTime'],
                        'toLat' => $point['lat'],
                        'toLng' => $point['lng'],
                        'markerLat' => $previous['lat'],
                        'markerLng' => $previous['lng'],
                        'duration' => max($elapsedSeconds, 0),
                        'distance' => round($distance, 2),
                    ];

                    continue;
                }

                if ($gapReason !== null) {
                    if ($idleRunDuration >= $stopSeconds) {
                        $stopCount++;
                    }
                    $idleRunDuration = 0;
                    $segment++;
                    $gaps[] = [
                        'reason' => $gapReason,
                        'fromTime' => $previous['gpsTime'],
                        'toTime' => $point['gpsTime'],
                        'toLat' => $point['lat'],
                        'toLng' => $point['lng'],
                        'markerLat' => $previous['lat'],
                        'markerLng' => $previous['lng'],
                        'duration' => max($elapsedSeconds, 0),
                        'distance' => round($distance, 2),
                    ];
                } else {
                    $totalDist += $distance;

                    if (max($previous['speed'], $point['speed']) >= self::MOVING_SPEED_THRESHOLD) {
                        if ($idleRunDuration >= $stopSeconds) {
                            $stopCount++;
                        }
                        $idleRunDuration = 0;
                        $movingDuration += $elapsedSeconds;
                    } else {
                        $idleDuration += $elapsedSeconds;
                        $idleRunDuration += $elapsedSeconds;
                    }
                }
            }

            $point['segment'] = $segment;
            unset($point['_timestamp']);
            $formatted[] = $point;
            $speeds[] = $point['speed'];
            if ($point['speed'] > $maxSpeed) {
                $maxSpeed = $point['speed'];
            }

            $previous = [...$point, '_timestamp' => Carbon::parse($point['gpsTime'])->getTimestamp()];
        }

        if ($idleRunDuration >= $stopSeconds) {
            $stopCount++;
        }

        if ($formatted === []) {
            return [
                ...$this->emptyTrackSummary(),
                'rawPointCount' => $rawPointCount,
                'invalidPointCount' => $invalidPointCount,
            ];
        }

        $avgSpeed = count($speeds) > 0 ? array_sum($speeds) / count($speeds) : 0;
        $startTime = $formatted[0]['gpsTime'] ?? null;
        $endTime = end($formatted)['gpsTime'] ?? null;

        $duration = 0;
        if ($startTime && $endTime) {
            $duration = Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime));
        }

        return [
            'points' => $formatted,
            'totalPoints' => count($formatted),
            'distance' => round($totalDist, 2),     // km
            'maxSpeed' => round($maxSpeed, 1),       // km/h
            'avgSpeed' => round($avgSpeed, 1),       // km/h
            'duration' => $duration,                  // seconds
            'startTime' => $startTime,
            'endTime' => $endTime,
            'rawPointCount' => $rawPointCount,
            'invalidPointCount' => $invalidPointCount,
            'duplicatePointCount' => $duplicatePointCount,
            'outlierPointCount' => $outlierPointCount,
            'gapCount' => count($gaps),
            'segmentCount' => $segment + 1,
            'gaps' => $gaps,
            'movingDuration' => $movingDuration,
            'idleDuration' => $idleDuration,
            'stopCount' => $stopCount,
        ];
    }

    private function emptyTrackSummary(): array
    {
        return [
            'points' => [],
            'totalPoints' => 0,
            'distance' => 0,
            'maxSpeed' => 0,
            'avgSpeed' => 0,
            'duration' => 0,
            'startTime' => null,
            'endTime' => null,
            'rawPointCount' => 0,
            'invalidPointCount' => 0,
            'duplicatePointCount' => 0,
            'outlierPointCount' => 0,
            'gapCount' => 0,
            'segmentCount' => 0,
            'gaps' => [],
            'movingDuration' => 0,
            'idleDuration' => 0,
            'stopCount' => 0,
        ];
    }

    private function parseTrackTimestamp(mixed $timestamp): ?Carbon
    {
        if (blank($timestamp)) {
            return null;
        }

        try {
            return Carbon::parse((string) $timestamp, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isValidTrackCoordinate(float $lat, float $lng): bool
    {
        return is_finite($lat)
            && is_finite($lng)
            && $lat >= -90
            && $lat <= 90
            && $lng >= -180
            && $lng <= 180
            && ! ($lat === 0.0 && $lng === 0.0);
    }

    /**
     * Haversine distance between two GPS coordinates in kilometers.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}
