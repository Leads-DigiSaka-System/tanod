<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Services\ActivityLogger;
use App\Services\Jimi\JimiDeviceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function index(Request $request, JimiDeviceService $jimiService)
    {
        // Fetch live locations from JIMI API for accurate online/offline status
        $liveLocations = $jimiService->fetchLiveLocations();

        // Helper: determine if a device is online based on JIMI data
        // (matches LiveView's resolveDeviceStatus logic: JIMI status=1 → online,
        //  fall back to hbTime within 10 min when status field is missing)
        $isOnline = function ($loc) {
            if (! $loc) return false;
            // Prefer JIMI's own online/offline flag
            if (array_key_exists('status', $loc)) {
                return (int) $loc['status'] === 1;
            }
            // Fallback: heartbeat within 10 minutes
            return ! empty($loc['hbTime'])
                && now()->diffInMinutes($loc['hbTime']) < 10;
        };

        $devices = Device::with('latestLocation', 'tractor')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('imei', 'like', "%{$s}%")
                    ->orWhere('device_name', 'like', "%{$s}%");
            }))
            ->when($request->status, function ($q, $status) use ($liveLocations, $isOnline) {
                if ($status === 'online') {
                    $imeis = [];
                    foreach ($liveLocations as $imei => $loc) {
                        if ($isOnline($loc)) $imeis[] = $imei;
                    }
                    $q->whereIn('imei', $imeis ?: ['__none__']);
                } elseif ($status === 'offline') {
                    $imeis = [];
                    foreach ($liveLocations as $imei => $loc) {
                        if ($isOnline($loc)) $imeis[] = $imei;
                    }
                    $q->whereNotIn('imei', $imeis ?: ['__none__']);
                } elseif ($status === 'unassigned') {
                    $q->whereDoesntHave('tractor');
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Merge live JIMI data into each device so the frontend can show accurate status
        $devices->getCollection()->transform(function ($device) use ($liveLocations, $isOnline) {
            $live = $liveLocations[$device->imei] ?? null;
            if ($live) {
                // JIMI's own status (1=online, 0=offline)
                $device->jimi_status = array_key_exists('status', $live)
                    ? ((int) $live['status'] === 1 ? 'online' : 'offline')
                    : null;
                // Heartbeat time for display
                $device->live_heartbeat_at = $live['hbTime'] ?? null;
            }
            return $device;
        });

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Device $device)
    {
        $device->load(['tractor', 'latestLocation', 'geoFences']);

        $recentLocations = DeviceLocation::where('device_id', $device->id)
            ->latest('heartbeat_at')
            ->take(100)
            ->get();

        return Inertia::render('Devices/Show', [
            'device' => $device,
            'recentLocations' => $recentLocations,
        ]);
    }

    public function syncAll(JimiDeviceService $jimiDeviceService)
    {
        $synced = $jimiDeviceService->syncDevicesFromJimi();

        ActivityLogger::log('Device', 0, 'synced', [
            'count' => $synced,
        ], request()->user());

        return back()->with('success', "Synced {$synced} devices from JIMI.");
    }

    public function syncLocations(JimiDeviceService $jimiDeviceService)
    {
        $locations = $jimiDeviceService->fetchAndStoreLocations(forceRefresh: true);

        ActivityLogger::log('Device', 0, 'synced_locations', [
            'count' => count($locations),
        ], request()->user());

        return back()->with('success', 'Synced locations for '.count($locations).' devices.');
    }

    public function locationHistory(Request $request, Device $device)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
        ]);

        $hasDateRange = filled($filters['from'] ?? null) && filled($filters['to'] ?? null);
        $tz = config('app.timezone', 'Asia/Manila');
        $from = $hasDateRange ? Carbon::parse($filters['from'], $tz)->startOfDay()->utc() : null;
        $to = $hasDateRange ? Carbon::parse($filters['to'], $tz)->endOfDay()->utc() : null;

        $locations = DeviceLocation::query()
            ->where('device_id', $device->id)
            ->when(
                $hasDateRange,
                fn ($query) => $query->whereBetween('heartbeat_at', [$from, $to]),
                fn ($query) => $query->whereRaw('0 = 1')
            )
            ->latest('heartbeat_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Devices/LocationHistory', [
            'device' => $device,
            'locations' => $locations,
            'filters' => $filters,
        ]);
    }
}
