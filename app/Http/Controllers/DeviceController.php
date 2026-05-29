<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Services\Jimi\JimiDeviceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::with('latestLocation', 'tractor')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('imei', 'like', "%{$s}%")
                    ->orWhere('device_name', 'like', "%{$s}%");
            }))
            ->when($request->status, function ($q, $status) {
                if ($status === 'online') {
                    $q->whereHas('latestLocation', fn ($q) => $q->where('heartbeat_at', '>=', now()->subMinutes(10)));
                } elseif ($status === 'offline') {
                    $q->whereDoesntHave('latestLocation', fn ($q) => $q->where('heartbeat_at', '>=', now()->subMinutes(10)));
                } elseif ($status === 'unassigned') {
                    $q->whereDoesntHave('tractor');
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

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

        return back()->with('success', "Synced {$synced} devices from JIMI.");
    }

    public function syncLocations(JimiDeviceService $jimiDeviceService)
    {
        $locations = $jimiDeviceService->fetchAndStoreLocations(forceRefresh: true);

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
