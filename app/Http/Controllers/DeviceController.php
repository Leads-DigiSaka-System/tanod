<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Services\Jimi\JimiDeviceService;
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

        return back()->with('success', 'Synced locations for ' . count($locations) . ' devices.');
    }

    public function locationHistory(Request $request, Device $device)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $locations = DeviceLocation::where('device_id', $device->id)
            ->whereBetween('heartbeat_at', [$request->from, $request->to])
            ->orderBy('heartbeat_at')
            ->get();

        return Inertia::render('Devices/LocationHistory', [
            'device' => $device,
            'locations' => $locations,
            'filters' => $request->only(['from', 'to']),
        ]);
    }
}
