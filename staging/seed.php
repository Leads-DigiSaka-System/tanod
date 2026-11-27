<?php

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/fixtures.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$password = getenv('TANOD_STAGING_PASSWORD');
if (! $app->environment('staging') || config('database.default') !== 'sqlite'
    || config('database.connections.sqlite.database') !== storage_path('app/tanod-staging.sqlite')
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

foreach (STAGING_TRACTORS as $imei => $sample) {
    $device = Device::updateOrCreate(['imei' => (string) $imei], [
        'device_name' => $sample['name'],
        'is_active' => true,
    ]);
    Tractor::updateOrCreate(['device_id' => $device->id], [
        'imei' => (string) $imei,
        'no_plate' => $sample['name'],
        'brand' => 'Tanod',
        'model' => 'Staging fixture',
        'is_active' => true,
    ]);
    DeviceLocation::updateOrCreate(['device_id' => $device->id], [
        'imei' => (string) $imei,
        'lat' => $sample['lat'],
        'lng' => $sample['lng'],
        'status' => $sample['status'],
        'acc_status' => $sample['acc'],
        'heartbeat_at' => now()->subDays($sample['status'] ? 0 : 2),
    ]);
}
