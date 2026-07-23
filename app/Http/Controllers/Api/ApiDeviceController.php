<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceShare;
use App\Services\Jimi\JimiDeviceService;
use App\Services\Jimi\JimiTrackingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiDeviceController extends Controller
{
    private const MOVING_SPEED_THRESHOLD = 3.0;

    public function index(Request $request)
    {
        $devices = Device::with(['latestLocation', 'tractor:id,device_id,no_plate'])
            ->whereHas('tractor', fn (Builder $q) => $this->scopeByRole($q, $request->user()))
            ->when($request->search, fn ($q, $s) => $q->where('imei', 'like', "%{$s}%"))
            ->paginate($request->per_page ?? 15);

        return DeviceResource::collection($devices);
    }

    public function show(Request $request, Device $device)
    {
        $device = $this->findAccessibleDevice($request->user(), $device->id);
        $device->load(['latestLocation', 'tractor']);

        return new DeviceResource($device);
    }

    public function locations(Request $request)
    {
        $devices = Device::with(['latestLocation', 'tractor:id,device_id,no_plate'])
            ->where('is_active', true)
            ->whereHas('tractor', fn (Builder $q) => $this->scopeByRole($q, $request->user()))
            ->whereHas('latestLocation')
            ->get();

        return DeviceResource::collection($devices);
    }

    public function locationHistory(Request $request, Device $device)
    {
        $device = $this->findAccessibleDevice($request->user(), $device->id);

        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $locations = DeviceLocation::where('device_id', $device->id)
            ->whereBetween('heartbeat_at', [$request->from, $request->to])
            ->orderBy('heartbeat_at')
            ->get();

        return response()->json([
            'device' => new DeviceResource($device),
            'locations' => $locations,
        ]);
    }

    /**
     * Create a temporary share link for a device's live location.
     */
    public function createShare(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'duration' => 'nullable|integer|min:1|max:72',
        ]);

        $device = $this->findAccessibleDevice($request->user(), (int) $request->device_id);
        $duration = $request->input('duration', 1);
        $token = Str::random(48);

        $share = DeviceShare::create([
            'token' => $token,
            'device_id' => $device->id,
            'imei' => $device->imei,
            'device_name' => $device->tractor?->no_plate ?? $device->device_name ?? $device->imei,
            'created_by' => $request->user()->id,
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
     * Fetch live GPS locations for all devices directly from Jimi API.
     * Uses 10s cache for efficient polling. No DB persistence.
     */
    public function liveLocations(Request $request, JimiDeviceService $deviceService)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $devices = Device::with('tractor:id,device_id')
            ->where('is_active', true)
            ->whereHas('tractor', fn (Builder $q) => $this->scopeByRole($q, $user))
            ->get(['id', 'imei']);

        $liveMap = $deviceService->fetchLiveLocations();

        if ($liveMap === []) {
            return response()->json([
                'success' => false,
                'locations' => [],
                'message' => 'Live locations are temporarily unavailable.',
            ]);
        }

        $locations = [];
        foreach ($devices as $device) {
            $live = $liveMap[$device->imei] ?? null;
            if (! $live) {
                continue;
            }

            $locations[] = $this->formatLiveLocation($device, $live);
        }

        return response()->json([
            'success' => true,
            'locations' => $locations,
        ]);
    }

    /**
     * Real-time single-device location for the "Follow" feature.
     * Uses the shared 10-second JIMI cache and does not persist to the DB.
     */
    public function followDevice(Request $request, Device $device, JimiDeviceService $deviceService)
    {
        $device = $this->findAccessibleDevice($request->user(), $device->id);

        $live = $deviceService->fetchDeviceLocationRealtime($device->imei);

        if ($live === null) {
            return response()->json([
                'success' => false,
                'location' => null,
                'message' => 'Live location is temporarily unavailable.',
            ]);
        }

        return response()->json([
            'success' => true,
            'location' => $this->formatLiveLocation($device, $live),
        ]);
    }

    /**
     * Format a single device's live JIMI data into a consistent response.
     */
    private function formatLiveLocation(Device $device, array $live): array
    {
        $heartbeatAt = $this->parseJimiTimestamp($live['hbTime'] ?? null);
        $gpsAt = $this->parseJimiTimestamp($live['gpsTime'] ?? null);
        $minutesAgo = $heartbeatAt
            ? max((int) floor($heartbeatAt->diffInMinutes(now()->utc())), 0)
            : 999;
        $gpsMinutesAgo = $gpsAt
            ? max((int) floor($gpsAt->diffInMinutes(now()->utc())), 0)
            : null;
        $speed = (float) ($live['speed'] ?? 0);
        $accStatus = (bool) ($live['accStatus'] ?? false);
        $jimiStatus = array_key_exists('status', $live) ? (int) $live['status'] : null;

        return [
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => (float) ($live['lat'] ?? 0),
            'lng' => (float) ($live['lng'] ?? 0),
            'speed' => $speed,
            'direction' => (float) ($live['direction'] ?? 0),
            'status' => $jimiStatus ?? 0,
            'live_status' => $this->resolveLiveStatus(
                $jimiStatus,
                $minutesAgo,
                $speed,
                $accStatus,
                $gpsMinutesAgo ?? $minutesAgo,
            ),
            'acc_status' => (int) $accStatus,
            'heartbeat_at' => $live['hbTime'] ?? null,
            'heartbeat_at_iso' => $heartbeatAt?->toIso8601String(),
            'minutes_ago' => $minutesAgo,
            'gps_time' => $gpsAt?->toIso8601String(),
            'gps_minutes_ago' => $gpsMinutesAgo,
        ];
    }

    private function parseJimiTimestamp(?string $timestamp): ?Carbon
    {
        if (blank($timestamp)) {
            return null;
        }

        try {
            return Carbon::parse($timestamp, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveLiveStatus(
        ?int $jimiStatus,
        int $minutesAgo,
        float $speed,
        bool $accStatus,
        int $gpsMinutesAgo,
    ): string {
        $isOnline = match ($jimiStatus) {
            1 => true,
            0 => false,
            default => $minutesAgo <= max((int) config('jimi.online_threshold_minutes', 8), 1),
        };

        if (! $isOnline) {
            return 'offline';
        }

        if (! $accStatus) {
            return 'parked';
        }

        $hasFreshMovement = $speed >= self::MOVING_SPEED_THRESHOLD
            && $gpsMinutesAgo <= max((int) config('jimi.movement_freshness_minutes', 5), 1);

        return $hasFreshMovement ? 'moving' : 'idling';
    }

    /**
     * Fetch historical GPS track data for a device by merging two data sources:
     *
     * 1. JIMI API (jimi.device.track.list) — primary, full historical track
     * 2. Local device_locations table — fallback for any extra recent pings
     *
     * Points are merged, deduplicated by lat/lng/gps_time, and sorted chronologically.
     */
    public function trackData(Request $request, JimiTrackingService $trackingService)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'period' => 'required|in:today,yesterday,3days,week,month,custom',
            'from' => 'nullable|date|required_if:period,custom',
            'to' => 'nullable|date|required_if:period,custom|after_or_equal:from',
        ]);

        $device = $this->findAccessibleDevice($request->user(), (int) $request->device_id);
        $imei = $device->imei;

        [$beginTime, $endTime] = $this->calculateDateRange(
            $request->period,
            $request->from,
            $request->to
        );

        // ── 1. Fetch from JIMI API (cached 5 min) ──
        $cacheKey = "mobile_track_data_v3_{$imei}_{$beginTime}_{$endTime}";

        $cacheSeconds = $request->period === 'today' ? 60 : 300;
        $jimiResult = Cache::get($cacheKey);

        if ($jimiResult === null) {
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

            $jimiResult = ['points' => $allPoints, 'warnings' => $warnings];

            if ($warnings === []) {
                Cache::put($cacheKey, $jimiResult, $cacheSeconds);
            }
        }

        // ── 2. Also fetch from local device_locations (not cached) ──
        $localLocations = DeviceLocation::where('device_id', $device->id)
            ->whereBetween('heartbeat_at', [$beginTime, $endTime])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('heartbeat_at')
            ->get(['lat', 'lng', 'speed', 'direction', 'heartbeat_at']);

        // ── 3. Merge both sources, deduplicate by coordinate + time ──
        $track = $this->mergeTrackPoints($jimiResult['points'], $localLocations);

        return response()->json([
            'success' => $track['points'] !== [] || $jimiResult['warnings'] === [],
            'partial' => $track['points'] !== [] && $jimiResult['warnings'] !== [],
            'warnings' => $jimiResult['warnings'],
            'device_id' => $device->id,
            'period' => $request->period,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'timezone' => config('jimi.display_timezone', 'Asia/Manila'),
            'begin_time_local' => Carbon::parse($beginTime, 'UTC')->setTimezone(config('jimi.display_timezone', 'Asia/Manila'))->toIso8601String(),
            'end_time_local' => Carbon::parse($endTime, 'UTC')->setTimezone(config('jimi.display_timezone', 'Asia/Manila'))->toIso8601String(),
            'points' => $track['points'],
            'track' => $track,
        ]);
    }

    /**
     * Merge raw JIMI API points with local DB records, deduplicating
     * by rounded lat/lng and gps time so the same ping isn't counted twice.
     */
    private function mergeTrackPoints(array $jimiRaw, $localLocations): array
    {
        $seen = [];
        $merged = [];
        $invalidPointCount = 0;
        $duplicatePointCount = 0;

        // ── Process JIMI API points first ──
        foreach ($jimiRaw as $p) {
            $lat = filter_var($p['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($p['lng'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($lat === false || $lng === false || ! $this->isValidTrackCoordinate($lat, $lng)) {
                $invalidPointCount++;

                continue;
            }

            $gpsTime = $this->parseJimiTimestamp($p['gpsTime'] ?? $p['positionTime'] ?? null)?->toIso8601String();
            if ($gpsTime === null) {
                $invalidPointCount++;

                continue;
            }
            $key = $this->pointDedupKey($lat, $lng, $gpsTime);

            if (isset($seen[$key])) {
                $duplicatePointCount++;

                continue;
            }

            $seen[$key] = true;

            $merged[] = [
                'lat' => $lat,
                'lng' => $lng,
                'speed' => (float) ($p['speed'] ?? $p['gpsSpeed'] ?? 0),
                'direction' => (float) ($p['course'] ?? $p['direction'] ?? 0),
                'gps_time' => $gpsTime,
            ];
        }

        // ── Then add local DB records that aren't duplicates ──
        foreach ($localLocations as $loc) {
            $lat = (float) $loc->lat;
            $lng = (float) $loc->lng;

            if ($lat == 0 && $lng == 0) {
                $invalidPointCount++;

                continue;
            }

            $gpsTime = $loc->heartbeat_at?->toIso8601String();
            $key = $this->pointDedupKey($lat, $lng, $gpsTime);

            if (isset($seen[$key])) {
                $duplicatePointCount++;

                continue;
            }

            $seen[$key] = true;

            $merged[] = [
                'lat' => $lat,
                'lng' => $lng,
                'speed' => (float) ($loc->speed ?? 0),
                'direction' => (float) ($loc->direction ?? 0),
                'gps_time' => $gpsTime,
            ];
        }

        usort($merged, fn (array $left, array $right): int => Carbon::parse($left['gps_time'])->getTimestamp() <=> Carbon::parse($right['gps_time'])->getTimestamp());

        return $this->segmentMobileTrackPoints(
            $merged,
            count($jimiRaw) + $localLocations->count(),
            $invalidPointCount,
            $duplicatePointCount,
        );
    }

    private function segmentMobileTrackPoints(
        array $points,
        int $rawPointCount,
        int $invalidPointCount,
        int $duplicatePointCount,
    ): array {
        $accepted = [];
        $previous = null;
        $segment = 0;
        $outlierPointCount = 0;
        $gaps = [];
        $distance = 0.0;
        $movingDuration = 0;
        $idleDuration = 0;
        $idleRunDuration = 0;
        $stopCount = 0;
        $gapSeconds = max((int) config('jimi.track_gap_minutes', 10), 1) * 60;
        $maxPlausibleSpeed = max((float) config('jimi.track_max_plausible_speed_kph', 120), 1);
        $stopSeconds = max((int) config('jimi.track_stop_minutes', 5), 1) * 60;

        foreach ($points as $point) {
            if (! $this->isValidTrackCoordinate($point['lat'], $point['lng'])) {
                $invalidPointCount++;

                continue;
            }

            $timestamp = Carbon::parse($point['gps_time'])->getTimestamp();

            if ($previous !== null) {
                $elapsedSeconds = $timestamp - $previous['_timestamp'];
                $segmentDistance = $this->haversineDistance(
                    $previous['lat'],
                    $previous['lng'],
                    $point['lat'],
                    $point['lng'],
                );
                $impliedSpeed = $elapsedSeconds > 0 ? $segmentDistance / ($elapsedSeconds / 3600) : INF;

                if ($elapsedSeconds > $gapSeconds) {
                    if ($idleRunDuration >= $stopSeconds) {
                        $stopCount++;
                    }
                    $idleRunDuration = 0;
                    $segment++;
                    $gaps[] = [
                        'reason' => 'time_gap',
                        'from_time' => $previous['gps_time'],
                        'to_time' => $point['gps_time'],
                        'duration' => $elapsedSeconds,
                        'marker_lat' => $previous['lat'],
                        'marker_lng' => $previous['lng'],
                    ];
                } elseif ($elapsedSeconds <= 0 || $impliedSpeed > $maxPlausibleSpeed) {
                    $outlierPointCount++;
                    $gaps[] = [
                        'reason' => 'implausible_jump',
                        'from_time' => $previous['gps_time'],
                        'to_time' => $point['gps_time'],
                        'duration' => max($elapsedSeconds, 0),
                        'marker_lat' => $previous['lat'],
                        'marker_lng' => $previous['lng'],
                    ];

                    continue;
                } else {
                    $distance += $segmentDistance;
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
            $accepted[] = $point;
            $previous = [...$point, '_timestamp' => $timestamp];
        }

        if ($idleRunDuration >= $stopSeconds) {
            $stopCount++;
        }

        return [
            'points' => $accepted,
            'raw_point_count' => $rawPointCount,
            'total_points' => count($accepted),
            'invalid_point_count' => $invalidPointCount,
            'duplicate_point_count' => $duplicatePointCount,
            'outlier_point_count' => $outlierPointCount,
            'segment_count' => $accepted === [] ? 0 : $segment + 1,
            'gap_count' => count($gaps),
            'gaps' => $gaps,
            'distance_km' => round($distance, 2),
            'moving_duration' => $movingDuration,
            'idle_duration' => $idleDuration,
            'stop_count' => $stopCount,
        ];
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

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Build a stable dedup key from lat/lng/gps_time (rounded to 4 decimals).
     */
    private function pointDedupKey(float $lat, float $lng, ?string $gpsTime): string
    {
        $rLat = round($lat, 4);
        $rLng = round($lng, 4);
        $time = $gpsTime ?? 'null';

        return "{$rLat}|{$rLng}|{$time}";
    }

    /**
     * Split a date range into smaller chunks (JIMI API works best with ≤2-day windows).
     */
    private function getDateChunks(string $beginTime, string $endTime, int $days = 2): array
    {
        $chunks = [];
        $current = Carbon::parse($beginTime);
        $end = Carbon::parse($endTime);

        while ($current < $end) {
            $chunkEnd = $current->copy()->addDays($days);
            if ($chunkEnd > $end) {
                $chunkEnd = $end;
            }
            $chunks[] = [$current->format('Y-m-d H:i:s'), $chunkEnd->format('Y-m-d H:i:s')];
            $current = $chunkEnd;
        }

        return $chunks;
    }

    /**
     * Scope a tractor query to only tractors visible to the given user.
     */
    private function scopeByRole(Builder $query, \App\Models\User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return;
        }

        $tractorIds = $user->accessibleTractorIds();

        if (empty($tractorIds)) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('tractors.id', $tractorIds);
    }

    private function findAccessibleDevice(\App\Models\User $user, int $deviceId): Device
    {
        return Device::query()
            ->whereKey($deviceId)
            ->whereHas('tractor', fn (Builder $query) => $this->scopeByRole($query, $user))
            ->firstOrFail();
    }

    private function calculateDateRange(string $period, ?string $from, ?string $to): array
    {
        $tz = config('jimi.display_timezone', 'Asia/Manila');

        [$begin, $end] = match ($period) {
            'today' => [Carbon::now($tz)->startOfDay(), Carbon::now($tz)],
            'yesterday' => [Carbon::now($tz)->subDay()->startOfDay(), Carbon::now($tz)->subDay()->endOfDay()],
            '3days' => [Carbon::now($tz)->subDays(3)->startOfDay(), Carbon::now($tz)],
            'week' => [Carbon::now($tz)->subDays(7)->startOfDay(), Carbon::now($tz)],
            'month' => [Carbon::now($tz)->subDays(30)->startOfDay(), Carbon::now($tz)],
            'custom' => [Carbon::parse($from, $tz)->startOfDay(), Carbon::parse($to, $tz)->endOfDay()],
            default => [Carbon::now($tz)->subDays(3)->startOfDay(), Carbon::now($tz)],
        };

        return [$begin->utc()->format('Y-m-d H:i:s'), $end->utc()->format('Y-m-d H:i:s')];
    }
}
