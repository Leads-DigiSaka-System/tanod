<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceShare;
use App\Services\Jimi\JimiDeviceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiDeviceController extends Controller
{
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
     * Direct JIMI API call — no cache, no DB persistence.
     */
    public function followDevice(Request $request, Device $device, JimiDeviceService $deviceService)
    {
        $device = $this->findAccessibleDevice($request->user(), $device->id);

        $locations = $deviceService->fetchLocationsRealtime();
        $live = $locations[$device->imei] ?? null;

        return response()->json([
            'success' => true,
            'location' => $live ? $this->formatLiveLocation($device, $live) : null,
        ]);
    }

    /**
     * Format a single device's live JIMI data into a consistent response.
     */
    private function formatLiveLocation(Device $device, array $live): array
    {
        return [
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => (float) ($live['lat'] ?? 0),
            'lng' => (float) ($live['lng'] ?? 0),
            'speed' => (float) ($live['speed'] ?? 0),
            'direction' => (float) ($live['direction'] ?? 0),
            'status' => (int) ($live['status'] ?? 0),
            'acc_status' => (int) ($live['accStatus'] ?? 0),
            'heartbeat_at' => $live['hbTime'] ?? null,
        ];
    }

    /**
     * Fetch historical GPS track data from the database for a device.
     * Uses recorded device_locations for smooth playback.
     */
    public function trackData(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'period' => 'required|in:today,yesterday,3days,week,month,custom',
            'from' => 'nullable|date|required_if:period,custom',
            'to' => 'nullable|date|required_if:period,custom',
        ]);

        $device = $this->findAccessibleDevice($request->user(), (int) $request->device_id);

        [$beginTime, $endTime] = $this->calculateDateRange(
            $request->period,
            $request->from,
            $request->to
        );

        $points = DeviceLocation::where('device_id', $device->id)
            ->whereBetween('heartbeat_at', [$beginTime, $endTime])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('heartbeat_at')
            ->get(['lat', 'lng', 'speed', 'direction', 'heartbeat_at'])
            ->map(fn (DeviceLocation $loc) => [
                'lat' => $loc->lat,
                'lng' => $loc->lng,
                'speed' => $loc->speed ?? 0.0,
                'direction' => (float) $loc->direction,
                'gps_time' => $loc->heartbeat_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'device_id' => $device->id,
            'period' => $request->period,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'points' => $points,
        ]);
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
        $tz = config('app.timezone', 'Asia/Manila');

        [$begin, $end] = match ($period) {
            'today' => [Carbon::now($tz)->startOfDay(), Carbon::now($tz)],
            'yesterday' => [Carbon::now($tz)->subDay()->startOfDay(), Carbon::now($tz)->subDay()->endOfDay()],
            '3days' => [Carbon::now($tz)->subDays(3)->startOfDay(), Carbon::now($tz)],
            'week' => [Carbon::now($tz)->startOfWeek(), Carbon::now($tz)],
            'month' => [Carbon::now($tz)->startOfMonth(), Carbon::now($tz)],
            'custom' => [Carbon::parse($from, $tz)->startOfDay(), Carbon::parse($to, $tz)->endOfDay()],
            default => [Carbon::now($tz)->subDays(3)->startOfDay(), Carbon::now($tz)],
        };

        return [$begin->utc()->format('Y-m-d H:i:s'), $end->utc()->format('Y-m-d H:i:s')];
    }
}
