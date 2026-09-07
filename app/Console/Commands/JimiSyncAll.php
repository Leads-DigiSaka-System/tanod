<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Services\Jimi\JimiAuthService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Single command to fetch all devices + locations from JIMI TrackSolidPro,
 * store/update them in the database, auto-create groups and tractors.
 *
 * Usage: php artisan jimi:sync-all
 */
class JimiSyncAll extends Command
{
    protected $signature = 'jimi:sync-all
                            {--fresh : Truncate existing device data before syncing}
                            {--no-locations : Skip fetching live locations}
                            {--no-tractors : Skip auto-creating tractor records}';

    protected $description = 'Fetch all devices and their details from JIMI, store in database (devices, groups, locations, tractors)';

    private JimiAuthService $jimi;

    private int $devicesCreated = 0;

    private int $devicesUpdated = 0;

    private int $groupsCreated = 0;

    private int $locationsStored = 0;

    private int $tractorsCreated = 0;

    public function handle(JimiAuthService $jimi): int
    {
        $this->jimi = $jimi;

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     JIMI TrackSolidPro — Full Sync       ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // ─── Step 0: Verify credentials ─────────────────────────────────
        $this->info('🔐 Authenticating with JIMI API...');

        try {
            $token = $this->jimi->getAccessToken();
            $this->info('   ✅ Token acquired: '.substr($token, 0, 20).'...');
        } catch (\Exception $e) {
            $this->error('   ❌ Authentication failed: '.$e->getMessage());
            $this->error('');
            $this->error('   Check your .env values:');
            $this->error('   JIMI_APP_KEY, JIMI_API_SECRET, JIMI_USER_ID, JIMI_USER_PWD_MD5');

            return self::FAILURE;
        }

        // ─── Step 1: Fetch all devices ──────────────────────────────────
        $this->newLine();
        $this->info('📡 Fetching device list from JIMI...');

        $deviceListResponse = $this->jimi->call('jimi.user.device.list', [
            'target' => config('jimi.user_id'),
        ]);

        if (((int) ($deviceListResponse['code'] ?? -1)) !== 0) {
            $this->error('   ❌ Device list API failed: '.($deviceListResponse['message'] ?? 'Unknown error'));
            $this->error('   Response code: '.($deviceListResponse['code'] ?? 'N/A'));

            return self::FAILURE;
        }

        $apiDevices = $deviceListResponse['result'] ?? [];
        $this->info('   📋 Found '.count($apiDevices).' devices on JIMI');

        if (empty($apiDevices)) {
            $this->warn('   No devices returned. Nothing to sync.');

            return self::SUCCESS;
        }

        // ─── Step 2: Sync groups from device data ───────────────────────
        $this->newLine();
        $this->info('📂 Syncing groups...');
        $groupMap = $this->syncGroups($apiDevices);
        $this->info("   ✅ Groups: {$this->groupsCreated} created, ".count($groupMap).' total');

        // ─── Step 3: Store/update devices ───────────────────────────────
        $this->newLine();
        $this->info('💾 Storing devices...');

        $progressBar = $this->output->createProgressBar(count($apiDevices));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('');

        foreach ($apiDevices as $deviceData) {
            $imei = $deviceData['imei'] ?? null;
            if (! $imei) {
                $progressBar->advance();

                continue;
            }

            $progressBar->setMessage($imei);

            $attributes = [
                'device_name' => $deviceData['deviceName'] ?? null,
                'device_model' => $deviceData['mcType'] ?? null,
                'sim' => $deviceData['sim'] ?? null,
                'mc_type' => $deviceData['mcType'] ?? null,
                'mc_type_use_scope' => $deviceData['mcTypeUseScope'] ?? null,
                'remark' => $deviceData['reMark'] ?? null,
                'activation_time' => ! empty($deviceData['activationTime'])
                    ? Carbon::parse($deviceData['activationTime']) : null,
                'expiration_date' => ! empty($deviceData['expiration'])
                    ? Carbon::parse($deviceData['expiration']) : null,
                'is_active' => true,
            ];

            $device = Device::withTrashed()->where('imei', $imei)->first();

            if ($device) {
                if ($device->trashed()) {
                    // Device was intentionally deleted by the user — skip it
                    continue;
                }
                $device->update($attributes);
                $this->devicesUpdated++;
            } else {
                $device = Device::create(array_merge(['imei' => $imei], $attributes));
                $this->devicesCreated++;
            }

            // Link tractor to group via pivot (devices reach groups through tractors)
            $jimiGroupName = $deviceData['deviceGroup'] ?? null;
            if ($jimiGroupName && isset($groupMap[$jimiGroupName])) {
                $tractor = $device->tractor;
                if ($tractor) {
                    $tractor->groups()->syncWithoutDetaching([$groupMap[$jimiGroupName]]);
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("   ✅ Devices: {$this->devicesCreated} created, {$this->devicesUpdated} updated");

        // ─── Step 4: Fetch & store live locations ───────────────────────
        if (! $this->option('no-locations')) {
            $this->newLine();
            $this->info('🌍 Fetching live locations for all devices...');

            $locationResponse = $this->jimi->call('jimi.user.device.location.list', [
                'target' => config('jimi.user_id'),
                'map_type' => 'GOOGLE',
            ]);

            if (((int) ($locationResponse['code'] ?? -1)) === 0) {
                $locationResults = $locationResponse['result'] ?? [];
                $this->info('   📍 Received '.count($locationResults).' location records');

                foreach ($locationResults as $item) {
                    $locImei = $item['imei'] ?? null;
                    if (! $locImei) {
                        continue;
                    }

                    $device = Device::where('imei', $locImei)->first();
                    if (! $device) {
                        continue;
                    }

                    DeviceLocation::create([
                        'device_id' => $device->id,
                        'imei' => $locImei,
                        'lat' => $item['lat'] ?? null,
                        'lng' => $item['lng'] ?? null,
                        'speed' => $item['speed'] ?? 0,
                        'direction' => $item['direction'] ?? null,
                        'status' => (int) ($item['status'] ?? 0),
                        'acc_status' => (int) ($item['accStatus'] ?? 0),
                        'gps_num' => (int) ($item['gpsNum'] ?? 0),
                        'pos_type' => $item['posType'] ?? null,
                        'heartbeat_at' => ! empty($item['hbTime'])
                            ? Carbon::parse($item['hbTime']) : null,
                        'raw_data' => $item,
                    ]);

                    $this->locationsStored++;
                }

                $this->info("   ✅ Locations stored: {$this->locationsStored}");
            } else {
                $this->warn('   ⚠️  Location API returned error: '.($locationResponse['message'] ?? 'Unknown'));
            }
        }

        // ─── Step 5: Auto-create tractor entries ────────────────────────
        if (! $this->option('no-tractors')) {
            $this->newLine();
            $this->info('🚜 Creating tractor records for devices without one...');

            $devicesWithoutTractor = Device::whereDoesntHave('tractor')
                ->where('is_active', true)
                ->get();

            foreach ($devicesWithoutTractor as $device) {
                $jimiDevice = collect($apiDevices)->firstWhere('imei', $device->imei);

                Tractor::create([
                    'imei' => $device->imei,
                    'device_id' => $device->id,
                    'no_plate' => $device->device_name ?: $device->imei,
                    'brand' => $jimiDevice['mcType'] ?? null,
                    'model' => $jimiDevice['mcType'] ?? null,
                    'is_active' => true,
                ]);

                $this->tractorsCreated++;
            }

            $this->info("   ✅ Tractors created: {$this->tractorsCreated}");
        }

        // ─── Summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║             Sync Complete                ║');
        $this->info('╚══════════════════════════════════════════╝');

        $this->table(
            ['Metric', 'Count'],
            [
                ['JIMI Devices Found', count($apiDevices)],
                ['Devices Created', $this->devicesCreated],
                ['Devices Updated', $this->devicesUpdated],
                ['Groups Created', $this->groupsCreated],
                ['Locations Stored', $this->locationsStored],
                ['Tractors Created', $this->tractorsCreated],
            ]
        );

        $this->newLine();
        Log::info('jimi:sync-all completed', [
            'api_devices' => count($apiDevices),
            'devices_created' => $this->devicesCreated,
            'devices_updated' => $this->devicesUpdated,
            'groups_created' => $this->groupsCreated,
            'locations_stored' => $this->locationsStored,
            'tractors_created' => $this->tractorsCreated,
        ]);

        return self::SUCCESS;
    }

    /**
     * Extract unique group names from the JIMI device list and sync to tractor_groups table.
     *
     * @return array<string, int> groupName => groupId
     */
    private function syncGroups(array $apiDevices): array
    {
        $groupMap = [];

        // Collect unique group names from JIMI data
        $jimiGroups = [];
        foreach ($apiDevices as $device) {
            $groupName = $device['deviceGroup'] ?? null;
            if ($groupName && ! in_array($groupName, $jimiGroups)) {
                $jimiGroups[] = $groupName;
            }
        }

        foreach ($jimiGroups as $groupName) {
            $group = TractorGroup::withTrashed()->where('name', $groupName)->first();

            if (! $group) {
                $group = TractorGroup::create([
                    'name' => $groupName,
                    'is_active' => true,
                ]);
                $this->groupsCreated++;
            } elseif ($group->trashed()) {
                $group->restore();
            }

            $groupMap[$groupName] = $group->id;
        }

        return $groupMap;
    }
}
