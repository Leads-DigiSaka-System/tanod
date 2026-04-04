<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeoFenceRequest;
use App\Models\GeoFence;
use App\Services\Jimi\JimiGeoFenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeoFenceController extends Controller
{
    public function index(Request $request)
    {
        $geoFences = GeoFence::with(['devices.tractor', 'creator:id,name'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('GeoFences/Index', [
            'geoFences' => $geoFences,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('GeoFences/Create', [
            'devices' => \App\Models\Device::with('tractor:id,device_id,no_plate')
                ->where('is_active', true)
                ->get(['id', 'imei', 'device_name']),
            'googleMapKey' => config('services.google.maps_key', env('GOOGLE_MAP_KEY', '')),
        ]);
    }

    public function store(StoreGeoFenceRequest $request, JimiGeoFenceService $jimiService)
    {
        $data = $request->validated();
        $deviceIds = $data['device_ids'];
        unset($data['device_ids']);

        $data['created_by'] = $request->user()->id;

        // Create locally
        $geoFence = GeoFence::create($data);
        $geoFence->devices()->attach($deviceIds);

        // Sync to JIMI for each device
        $devices = \App\Models\Device::whereIn('id', $deviceIds)->get();
        foreach ($devices as $device) {
            try {
                $params = [
                    'fence_name' => $geoFence->name,
                    'fence_shape' => $geoFence->shape === 'circle' ? 0 : 1,
                ];
                if ($geoFence->shape === 'circle') {
                    $params['center_lat'] = $geoFence->center_lat;
                    $params['center_lng'] = $geoFence->center_lng;
                    $params['radius'] = $geoFence->radius;
                } else {
                    $coords = is_string($geoFence->coordinates) ? json_decode($geoFence->coordinates, true) : $geoFence->coordinates;
                    $params['coordinates'] = json_encode($coords);
                }
                $jimiService->createGeoFence($device->imei, $params);
            } catch (\Exception $e) {
                logger()->warning('Failed to sync geofence to JIMI for device '.$device->imei, ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('geofences.index')
            ->with('success', 'Geo-fence created successfully.');
    }

    public function show(GeoFence $geoFence)
    {
        $geoFence->load(['devices.tractor', 'creator:id,name', 'alerts' => fn ($q) => $q->latest()->take(10)]);

        return Inertia::render('GeoFences/Show', [
            'geoFence' => $geoFence,
            'googleMapKey' => config('services.google.maps_key', env('GOOGLE_MAP_KEY', '')),
        ]);
    }

    public function destroy(GeoFence $geoFence, JimiGeoFenceService $jimiService)
    {
        foreach ($geoFence->devices as $device) {
            try {
                $jimiService->deleteGeoFence($device->imei, $geoFence->name);
            } catch (\Exception $e) {
                logger()->warning('Failed to delete geofence from JIMI for device '.$device->imei, ['error' => $e->getMessage()]);
            }
        }

        $geoFence->devices()->detach();
        $geoFence->delete();

        return redirect()->route('geofences.index')
            ->with('success', 'Geo-fence deleted.');
    }
}
