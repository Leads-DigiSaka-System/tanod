<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\GeoFence;
use App\Services\Jimi\JimiGeoFenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiGeoFenceController extends Controller
{
    /**
     * List geofences relevant to the authenticated user.
     * - admin: all
     * - tps: geofences for devices in their group tractors
     * - fca: geofences for devices on their distributed tractors
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = GeoFence::with(['devices.tractor:id,device_id,no_plate,brand,model'])
            ->where('is_active', true)
            ->latest();

        $this->scopeByRole($query, $user);

        return response()->json(
            $query->paginate($request->per_page ?? 20)
        );
    }

    /**
     * Show a single geofence with its devices and recent alerts.
     */
    public function show(Request $request, GeoFence $geoFence)
    {
        $user = $request->user();

        // Verify access
        $check = GeoFence::where('id', $geoFence->id);
        $this->scopeByRole($check, $user);
        abort_unless($check->exists(), 403);

        $geoFence->load([
            'devices.tractor:id,device_id,no_plate,brand,model',
            'alerts' => fn ($q) => $q->latest()->take(10),
        ]);

        return response()->json(['data' => $geoFence]);
    }

    /**
     * Create a geofence.
     * FCA can create for devices on their assigned tractors.
     * TPS / admin can create for devices in their scope.
     */
    public function store(Request $request, JimiGeoFenceService $jimiService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['required', 'integer', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:255'],
            'shape' => ['required', 'in:circle,polygon'],
            'center_lat' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-180,180'],
            'radius' => ['required_if:shape,circle', 'nullable', 'numeric', 'min:50', 'max:100000'],
            'coordinates' => ['required_if:shape,polygon', 'nullable', 'array', 'min:3'],
            'coordinates.*.lat' => ['required_with:coordinates', 'numeric', 'between:-90,90'],
            'coordinates.*.lng' => ['required_with:coordinates', 'numeric', 'between:-180,180'],
            'alert_on' => ['required', 'in:enter,exit,both'],
        ]);

        // Verify the user has access to these devices
        $accessibleDeviceIds = $this->accessibleDeviceIds($user);
        $requestedIds = collect($validated['device_ids']);
        $unauthorized = $requestedIds->diff($accessibleDeviceIds);

        if ($unauthorized->isNotEmpty()) {
            return response()->json(['message' => 'You do not have access to some of the selected devices.'], 403);
        }

        $deviceIds = $validated['device_ids'];
        unset($validated['device_ids']);

        $validated['created_by'] = $user->id;
        $validated['is_active'] = true;

        $geoFence = GeoFence::create($validated);
        $geoFence->devices()->attach($deviceIds);

        // Sync to JIMI for each device
        $devices = Device::whereIn('id', $deviceIds)->get();
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
                    $params['coordinates'] = json_encode($geoFence->coordinates);
                }
                $jimiService->createGeoFence($device->imei, $params);
            } catch (\Exception $e) {
                logger()->warning("API: Failed to sync geofence to JIMI for device {$device->imei}", ['error' => $e->getMessage()]);
            }
        }

        $geoFence->load('devices.tractor:id,device_id,no_plate,brand,model');

        return response()->json(['data' => $geoFence, 'message' => 'Geofence created.'], 201);
    }

    /**
     * Update a geofence (owner or admin only).
     */
    public function update(Request $request, GeoFence $geoFence, JimiGeoFenceService $jimiService)
    {
        $user = $request->user();

        $isOwner = $geoFence->created_by === $user->id;
        $isAdmin = $user->hasAnyRole(['super-admin', 'sub-admin']);

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['required', 'integer', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:255'],
            'shape' => ['required', 'in:circle,polygon'],
            'center_lat' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-180,180'],
            'radius' => ['required_if:shape,circle', 'nullable', 'numeric', 'min:50', 'max:100000'],
            'coordinates' => ['required_if:shape,polygon', 'nullable', 'array', 'min:3'],
            'coordinates.*.lat' => ['required_with:coordinates', 'numeric', 'between:-90,90'],
            'coordinates.*.lng' => ['required_with:coordinates', 'numeric', 'between:-180,180'],
            'alert_on' => ['required', 'in:enter,exit,both'],
        ]);

        // Verify the user has access to these devices
        $accessibleDeviceIds = $this->accessibleDeviceIds($user);
        $requestedIds = collect($validated['device_ids']);
        $unauthorized = $requestedIds->diff($accessibleDeviceIds);

        if ($unauthorized->isNotEmpty()) {
            return response()->json(['message' => 'You do not have access to some of the selected devices.'], 403);
        }

        $deviceIds = $validated['device_ids'];
        unset($validated['device_ids']);

        // Delete old JIMI geofences
        foreach ($geoFence->devices as $device) {
            try {
                $jimiService->deleteGeoFence($device->imei, $geoFence->name);
            } catch (\Exception $e) {
                logger()->warning("API: Failed to delete old geofence from JIMI for device {$device->imei}", ['error' => $e->getMessage()]);
            }
        }

        $geoFence->update($validated);
        $geoFence->devices()->sync($deviceIds);

        // Sync new geofence to JIMI for each device
        $devices = Device::whereIn('id', $deviceIds)->get();
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
                    $params['coordinates'] = json_encode($geoFence->coordinates);
                }
                $jimiService->createGeoFence($device->imei, $params);
            } catch (\Exception $e) {
                logger()->warning("API: Failed to sync geofence to JIMI for device {$device->imei}", ['error' => $e->getMessage()]);
            }
        }

        $geoFence->load('devices.tractor:id,device_id,no_plate,brand,model');

        return response()->json(['data' => $geoFence, 'message' => 'Geofence updated.']);
    }

    /**
     * Delete a geofence (owner or admin only).
     */
    public function destroy(Request $request, GeoFence $geoFence, JimiGeoFenceService $jimiService)
    {
        $user = $request->user();

        $isOwner = $geoFence->created_by === $user->id;
        $isAdmin = $user->hasAnyRole(['super-admin', 'sub-admin']);

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        foreach ($geoFence->devices as $device) {
            try {
                $jimiService->deleteGeoFence($device->imei, $geoFence->name);
            } catch (\Exception $e) {
                logger()->warning("API: Failed to delete geofence from JIMI for device {$device->imei}", ['error' => $e->getMessage()]);
            }
        }

        $geoFence->devices()->detach();
        $geoFence->delete();

        return response()->json(['message' => 'Geofence deleted.']);
    }

    /**
     * List devices accessible to the user (for the create form).
     */
    public function devices(Request $request)
    {
        $user = $request->user();
        $ids = $this->accessibleDeviceIds($user);

        $devices = Device::whereIn('id', $ids)
            ->where('is_active', true)
            ->with('tractor:id,device_id,no_plate,brand,model')
            ->get(['id', 'imei', 'device_name']);

        return response()->json(['data' => $devices]);
    }

    /**
     * Scope geofences query by user role.
     */
    private function scopeByRole(Builder $query, \App\Models\User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return;
        }

        if ($user->hasRole('tps')) {
            $query->whereHas('devices.tractor.groups.users', fn (Builder $q) => $q->where('users.id', $user->id));
        } elseif ($user->hasRole('fca')) {
            $query->whereHas('devices.tractor.distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                ->where('status', 'distributed'));
        } else {
            $query->whereRaw('0 = 1');
        }
    }

    /**
     * Get device IDs accessible to the user.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function accessibleDeviceIds(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return Device::where('is_active', true)->pluck('id');
        }

        if ($user->hasRole('tps')) {
            return Device::where('is_active', true)
                ->whereHas('tractor.groups.users', fn (Builder $q) => $q->where('users.id', $user->id))
                ->pluck('id');
        }

        if ($user->hasRole('fca')) {
            return Device::where('is_active', true)
                ->whereHas('tractor.distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                    ->where('status', 'distributed'))
                ->pluck('id');
        }

        return collect();
    }
}
