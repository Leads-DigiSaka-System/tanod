<?php

namespace App\Services\Jimi;

use App\Models\Device;
use App\Models\DeviceLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Handles device listing and location queries from Jimi API,
 * and persists results into local database.
 */
class JimiDeviceService
{
    public function __construct(
        private JimiAuthService $auth,
    ) {}

    /**
     * Sync all devices from Jimi into local DB.
     * Uses: jimi.user.device.list (API 2.1)
     *
     * @return int Number of devices synced
     */
    public function syncDevicesFromJimi(): int
    {
        $response = $this->auth->call('jimi.user.device.list', [
            'target' => config('jimi.user_id'),
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            Log::warning('Jimi device list failed', ['response' => $response]);
            return 0;
        }

        $devices = $response['result'] ?? [];
        $synced = 0;

        foreach ($devices as $deviceData) {
            $imei = $deviceData['imei'] ?? null;
            if (!$imei) continue;

            Device::updateOrCreate(
                ['imei' => $imei],
                [
                    'device_name' => $deviceData['deviceName'] ?? null,
                    'device_model' => $deviceData['deviceModel'] ?? null,
                    'sim' => $deviceData['sim'] ?? null,
                    'activation_time' => !empty($deviceData['activationTime'])
                        ? \Carbon\Carbon::parse($deviceData['activationTime'])
                        : null,
                    'expiration_date' => !empty($deviceData['expirationDate'])
                        ? \Carbon\Carbon::parse($deviceData['expirationDate'])
                        : null,
                    'is_active' => true,
                ]
            );
            $synced++;
        }

        return $synced;
    }

    /**
     * Fetch live locations for all devices and store in DB.
     * Uses: jimi.user.device.location.list (API 3.1)
     *
     * @param bool $forceRefresh Bypass cache
     * @return array<string, array> IMEI => location data
     */
    public function fetchAndStoreLocations(bool $forceRefresh = false): array
    {
        $cacheKey = 'jimi_device_locations';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(config('jimi.location_cache_ttl')), function () {
            return $this->fetchLocationsFromApi();
        });
    }

    /**
     * Fetch live locations from Jimi API without persisting to DB.
     * Short-lived cache (10s) for efficient polling.
     * Uses: jimi.user.device.location.list (API 3.1)
     *
     * @return array<string, array> IMEI => location data
     */
    public function fetchLiveLocations(): array
    {
        return Cache::remember('jimi_live_locations', 10, function () {
            return $this->fetchLocationsRealtime();
        });
    }

    /**
     * Fetch real-time locations directly from JIMI API.
     * No cache, no DB persistence — pure API call.
     *
     * @return array<string, array> IMEI => location data
     */
    public function fetchLocationsRealtime(): array
    {
        $response = $this->auth->call('jimi.user.device.location.list', [
            'target' => config('jimi.user_id'),
            'map_type' => 'GOOGLE',
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            Log::warning('Jimi realtime location list failed', ['response' => $response]);

            return [];
        }

        $results = $response['result'] ?? [];
        $locationMap = [];

        foreach ($results as $item) {
            $imei = $item['imei'] ?? null;
            if (! $imei) {
                continue;
            }
            $locationMap[$imei] = $item;
        }

        return $locationMap;
    }

    /**
     * Fetch locations from API, store each in device_locations table.
     */
    private function fetchLocationsFromApi(): array
    {
        $response = $this->auth->call('jimi.user.device.location.list', [
            'target' => config('jimi.user_id'),
            'map_type' => 'GOOGLE',
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            Log::warning('Jimi location list failed', ['response' => $response]);
            return [];
        }

        $results = $response['result'] ?? [];
        $locationMap = [];

        foreach ($results as $item) {
            $imei = $item['imei'] ?? null;
            if (!$imei) continue;

            $device = Device::where('imei', $imei)->first();
            if (!$device) continue;

            DeviceLocation::create([
                'device_id' => $device->id,
                'imei' => $imei,
                'lat' => $item['lat'] ?? null,
                'lng' => $item['lng'] ?? null,
                'speed' => $item['speed'] ?? 0,
                'direction' => $item['direction'] ?? null,
                'status' => (int) ($item['status'] ?? 0),
                'acc_status' => (int) ($item['accStatus'] ?? 0),
                'gps_num' => (int) ($item['gpsNum'] ?? 0),
                'pos_type' => $item['posType'] ?? null,
                'heartbeat_at' => !empty($item['hbTime'])
                    ? \Carbon\Carbon::parse($item['hbTime'])
                    : null,
                'raw_data' => $item,
            ]);

            $locationMap[$imei] = $item;
        }

        return $locationMap;
    }

    /**
     * Get a single device's current location directly from API.
     * Uses: jimi.device.location.get (API 3.2)
     */
    public function getDeviceLocation(string $imei): ?array
    {
        $response = $this->auth->call('jimi.device.location.get', [
            'imei' => $imei,
            'map_type' => 'GOOGLE',
        ]);

        if (((int) ($response['code'] ?? -1)) !== 0) {
            return null;
        }

        $result = $response['result'] ?? null;

        if ($result) {
            $device = Device::where('imei', $imei)->first();
            if ($device) {
                DeviceLocation::create([
                    'device_id' => $device->id,
                    'imei' => $imei,
                    'lat' => $result['lat'] ?? null,
                    'lng' => $result['lng'] ?? null,
                    'speed' => $result['speed'] ?? 0,
                    'direction' => $result['direction'] ?? null,
                    'status' => (int) ($result['status'] ?? 0),
                    'acc_status' => (int) ($result['accStatus'] ?? 0),
                    'gps_num' => (int) ($result['gpsNum'] ?? 0),
                    'pos_type' => $result['posType'] ?? null,
                    'heartbeat_at' => !empty($result['hbTime'])
                        ? \Carbon\Carbon::parse($result['hbTime'])
                        : null,
                    'raw_data' => $result,
                ]);
            }
        }

        return $result;
    }

    /**
     * Get group mapping from Jimi.
     * Uses: jimi.user.device.list — extracts deviceGroupId / deviceGroup
     */
    public function fetchDeviceGroupMap(bool $forceRefresh = false): array
    {
        $cacheKey = 'jimi_device_group_map';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(config('jimi.device_cache_ttl')), function () {
            $response = $this->auth->call('jimi.user.device.list', [
                'target' => config('jimi.user_id'),
            ]);

            if (((int) ($response['code'] ?? -1)) !== 0) {
                return ['groups' => [], 'imeiGroup' => []];
            }

            $groups = [];
            $imeiGroup = [];

            foreach ($response['result'] ?? [] as $device) {
                $imei = $device['imei'] ?? null;
                $groupId = $device['deviceGroupId'] ?? null;
                $groupName = $device['deviceGroup'] ?? 'Ungrouped';

                if ($imei && $groupId) {
                    $imeiGroup[$imei] = $groupName;
                    if (!isset($groups[$groupId])) {
                        $groups[$groupId] = [
                            'id' => $groupId,
                            'name' => $groupName,
                            'devices' => [],
                        ];
                    }
                    $groups[$groupId]['devices'][] = $imei;
                }
            }

            return compact('groups', 'imeiGroup');
        });
    }
}
