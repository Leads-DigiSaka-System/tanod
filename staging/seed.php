<?php

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Tractor;
use App\Models\User;
use App\Services\Jimi\JimiDeviceService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__.'/fixtures.php';
$app = require __DIR__.'/bootstrap.php';
$app->make(Kernel::class)->bootstrap();

$password = getenv('TANOD_STAGING_PASSWORD');
$fleetMode = getenv('TANOD_STAGING_FLEET_MODE');
if (! $app->environment('staging') || config('database.default') !== 'sqlite'
    || ! in_array($fleetMode, ['sample', 'tracksolid', 'unconfigured'], true)
    || config('database.connections.sqlite.database') !== storage_path("app/tanod-staging-{$fleetMode}.sqlite")
    || ! is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "A staging-only SQLite database and 12-character test password are required.\n");
    exit(1);
}

$user = User::updateOrCreate([
    'email' => 'tester@tanod.example',
], [
    'name' => 'Tanod Staging Tester',
    'password' => Hash::make($password),
    'is_active' => true,
    'must_change_password' => false,
]);
$user->assignRole(Role::findOrCreate('super-admin'));

$locations = match ($fleetMode) {
    'sample' => stagingLiveLocations(),
    'tracksolid' => $app->make(JimiDeviceService::class)->fetchLiveLocations(),
    default => [],
};

DB::transaction(function () use ($locations): void {
    foreach ($locations as $location) {
        $imei = (string) ($location['imei'] ?? '');
        if ($imei === '') {
            continue;
        }

        $name = trim((string) ($location['deviceName'] ?? '')) ?: $imei;
        $device = Device::updateOrCreate(['imei' => $imei], [
            'device_name' => $name,
            'is_active' => true,
        ]);
        Tractor::updateOrCreate(['device_id' => $device->id], [
            'imei' => $imei,
            'no_plate' => $name,
            'is_active' => true,
        ]);
        $heartbeat = JimiDeviceService::parseLocationTime($location['hbTime'] ?? null)
            ?? JimiDeviceService::parseLocationTime($location['gpsTime'] ?? null);
        if ($heartbeat !== null) {
            DeviceLocation::updateOrCreate(['device_id' => $device->id], [
                'imei' => $imei,
                'lat' => $location['lat'] ?? null,
                'lng' => $location['lng'] ?? null,
                'status' => (int) ($location['status'] ?? 0),
                'acc_status' => filter_var($location['accStatus'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'heartbeat_at' => $heartbeat,
            ]);
        }
    }
});

if ($fleetMode === 'tracksolid') {
    fwrite(STDOUT, count($locations)." TrackSolid devices received.\n");
}
