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
            'to' => 'nullable|date|required_if:period,custom',
        ]);

        $device = $this->findAccessibleDevice($request, (int) $request->device_id);
        $imei = $device->imei;

        // Calculate date range based on period
        [$beginTime, $endTime] = $this->calculateDateRange(
            $request->period,
            $request->from,
            $request->to
        );

        // Cache key based on imei + date range
        $cacheKey = "track_data_{$imei}_{$beginTime}_{$endTime}";

        $trackPoints = Cache::remember($cacheKey, 300, function () use ($trackingService, $imei, $beginTime, $endTime) {
            // Split into 2-day chunks for JIMI API reliability
            $chunks = $this->getDateChunks($beginTime, $endTime, 2);
            $allPoints = [];

            foreach ($chunks as [$chunkStart, $chunkEnd]) {
                $points = $trackingService->fetchTrackData($imei, $chunkStart, $chunkEnd);
                if (is_array($points)) {
                    $allPoints = array_merge($allPoints, $points);
                }
            }

            return $allPoints;
        });

        // Process and return formatted track data
        $formatted = $this->formatTrackPoints($trackPoints);

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'imei' => $device->imei,
                'device_name' => $device->device_name,
                'no_plate' => $device->tractor?->no_plate,
            ],
            'period' => $request->period,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'track' => $formatted,
        ]);
    }

    /**
     * Real-time single-device location for the "Follow" feature.
     * Direct JIMI API call — no cache, no DB persistence.
     */
    public function followDevice(Request $request, Device $device, JimiDeviceService $jimiService)
    {
        $device = $this->findAccessibleDevice($request, $device->id);
        $locations = $jimiService->fetchLocationsRealtime();
        $item = $locations[$device->imei] ?? null;

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
            ? $heartbeatAt->diffInMinutes(now()->utc())
            : 999;

        $status = 'offline';
        if ($minutesAgo <= $this->onlineThresholdMinutes() && $loc) {
            $status = ($loc->speed ?? 0) > 0 ? 'moving' : 'idle';
        }

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
        $posType = null;
        $gpsNum = null;
        $mileage = null;

        if ($apiData) {
            $parsedHeartbeatAt = $this->parseHeartbeat($apiData['hbTime'] ?? null);
            $heartbeatAt = $parsedHeartbeatAt?->toIso8601String();
            $minutesAgo = $heartbeatAt
                ? $parsedHeartbeatAt->diffInMinutes(now()->utc())
                : 999;

            $lat = $apiData['lat'] ?? null;
            $lng = $apiData['lng'] ?? null;
            $speed = (float) ($apiData['speed'] ?? 0);
            $direction = (float) ($apiData['direction'] ?? 0);
            $accStatus = (bool) ($apiData['accStatus'] ?? false);
            $posType = $apiData['posType'] ?? null;
            $gpsNum = (int) ($apiData['gpsNum'] ?? 0);
            $mileage = $apiData['mileage'] ?? null;

            if ($minutesAgo <= $this->onlineThresholdMinutes()) {
                $status = $speed > 0 ? 'moving' : 'idle';
            }
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

        return Carbon::parse($heartbeatAt, 'UTC')->utc();
    }

    private function onlineThresholdMinutes(): int
    {
        return max((int) config('jimi.online_threshold_minutes', 10), 1);
    }

    private function accessibleDevicesQuery(Request $request): Builder
    {
        $user = $request->user();

        return Device::query()
            ->whereHas('tractor', fn (Builder $query) => $this->scopeTractorsByRole($query, $user));
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
        $tz = config('app.timezone', 'Asia/Manila');

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
            return [
                'points' => [],
                'totalPoints' => 0,
                'distance' => 0,
                'maxSpeed' => 0,
                'avgSpeed' => 0,
                'duration' => 0,
                'startTime' => null,
                'endTime' => null,
            ];
        }

        // Sort by GPS time
        usort($points, function ($a, $b) {
            return ($a['gpsTime'] ?? '') <=> ($b['gpsTime'] ?? '');
        });

        $formatted = [];
        $speeds = [];
        $maxSpeed = 0;
        $totalDist = 0;

        foreach ($points as $i => $p) {
            $lat = (float) ($p['lat'] ?? 0);
            $lng = (float) ($p['lng'] ?? 0);
            $speed = (float) ($p['speed'] ?? $p['gpsSpeed'] ?? 0);
            $dir = (float) ($p['course'] ?? $p['direction'] ?? 0);
            $time = $p['gpsTime'] ?? $p['positionTime'] ?? null;

            if ($lat == 0 && $lng == 0) {
                continue;
            }

            $formatted[] = [
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $speed,
                'direction' => $dir,
                'gpsTime' => $time,
            ];

            $speeds[] = $speed;
            if ($speed > $maxSpeed) {
                $maxSpeed = $speed;
            }

            // Calculate distance from previous point using Haversine
            if ($i > 0) {
                $prevLat = (float) ($points[$i - 1]['lat'] ?? 0);
                $prevLng = (float) ($points[$i - 1]['lng'] ?? 0);
                if ($prevLat != 0 && $prevLng != 0) {
                    $totalDist += $this->haversineDistance($prevLat, $prevLng, $lat, $lng);
                }
            }
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
        ];
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
