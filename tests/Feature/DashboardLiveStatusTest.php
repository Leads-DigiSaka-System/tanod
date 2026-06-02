<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Tractor;
use App\Models\User;
use App\Services\Jimi\JimiDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardLiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function admin_dashboard_uses_live_jimi_heartbeat_status_for_online_counts(): void
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        $onlineDevice = Device::create([
            'imei' => '869066063771901',
            'device_name' => 'TRC-ONLINE',
            'is_active' => true,
        ]);
        Tractor::create([
            'device_id' => $onlineDevice->id,
            'imei' => $onlineDevice->imei,
            'no_plate' => 'TRC-ONLINE',
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
            'total_distance' => 450.5,
            'running_hours' => 110.0,
        ]);

        $offlineDevice = Device::create([
            'imei' => '869066063771902',
            'device_name' => 'TRC-OFFLINE',
            'is_active' => true,
        ]);
        Tractor::create([
            'device_id' => $offlineDevice->id,
            'imei' => $offlineDevice->imei,
            'no_plate' => 'TRC-OFFLINE',
            'brand' => 'Kubota',
            'model' => 'L5018',
            'is_active' => true,
            'total_distance' => 200.0,
            'running_hours' => 55.0,
        ]);

        $inactiveDevice = Device::create([
            'imei' => '869066063771903',
            'device_name' => 'TRC-INACTIVE',
            'is_active' => false,
        ]);
        Tractor::create([
            'device_id' => $inactiveDevice->id,
            'imei' => $inactiveDevice->imei,
            'no_plate' => 'TRC-INACTIVE',
            'brand' => 'Kubota',
            'model' => 'M6040',
            'is_active' => true,
        ]);

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    '869066063771901' => [
                        'hbTime' => now()->utc()->subMinutes(4)->toIso8601String(),
                        'speed' => 12,
                    ],
                    '869066063771902' => [
                        'hbTime' => now()->utc()->subMinutes(18)->toIso8601String(),
                        'speed' => 0,
                    ],
                ]);
        });

        $response = $this->actingAs($admin)->get(route('dashboard', [], false));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.totalTractors', 3)
                ->where('stats.onlineTractors', 1)
                ->where('stats.offlineTractors', 1)
                ->where('stats.inactiveTractors', 1)
                ->where('stats.onlineDevices', 1)
                // Usage summary
                ->where('stats.totalDistance', 650.5)
                ->where('stats.avgDistancePerTractor', round(650.5 / 3, 2))
                ->where('stats.totalRunningHours', 165)
                ->where('stats.avgHoursPerTractor', 55)
                ->where('stats.tractorsWithUsageData', 2)
                ->where('stats.usageDataPercent', round((2 / 3) * 100, 1))
                ->where('stats.pmsDue', 1)
                ->where('stats.pmsOk', 1)
                ->where('stats.pmsNoData', 1)
                ->where('stats.totalMaintenanceRecords', 0)
                // Charts
                ->where('charts.tractorStatus.online', 1)
                ->where('charts.tractorStatus.offline', 1)
                ->where('charts.tractorStatus.inactive', 1)
                ->where('charts.pmsBreakdown.due', 1)
                ->where('charts.pmsBreakdown.ok', 1)
                ->where('charts.pmsBreakdown.noData', 1));
    }

    #[Test]
    public function jimi_status_field_overrides_heartbeat_threshold(): void
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        // Device A: recent heartbeat (2 min) but JIMI says offline (status=0)
        $deviceA = Device::create([
            'imei' => '869066063771910',
            'device_name' => 'JIMI-OFFLINE',
            'is_active' => true,
        ]);
        Tractor::create([
            'device_id' => $deviceA->id,
            'imei' => $deviceA->imei,
            'no_plate' => 'JIMI-OFFLINE',
            'brand' => 'Kubota',
            'model' => 'Test',
            'is_active' => true,
        ]);

        // Device B: stale heartbeat (15 min) but JIMI says online (status=1)
        $deviceB = Device::create([
            'imei' => '869066063771911',
            'device_name' => 'JIMI-ONLINE',
            'is_active' => true,
        ]);
        Tractor::create([
            'device_id' => $deviceB->id,
            'imei' => $deviceB->imei,
            'no_plate' => 'JIMI-ONLINE',
            'brand' => 'Kubota',
            'model' => 'Test',
            'is_active' => true,
        ]);

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    '869066063771910' => [
                        'hbTime' => now()->utc()->subMinutes(2)->toIso8601String(),
                        'speed' => 0,
                        'status' => 0, // JIMI says offline despite recent heartbeat
                    ],
                    '869066063771911' => [
                        'hbTime' => now()->utc()->subMinutes(15)->toIso8601String(),
                        'speed' => 5,
                        'status' => 1, // JIMI says online despite stale heartbeat
                    ],
                ]);
        });

        $response = $this->actingAs($admin)->get(route('dashboard', [], false));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                // Device A: JIMI status=0 → offline (despite 2-min heartbeat)
                // Device B: JIMI status=1 → online (despite 15-min heartbeat)
                ->where('stats.onlineTractors', 1)
                ->where('stats.offlineTractors', 1)
                ->where('stats.inactiveTractors', 0)
                ->where('stats.onlineDevices', 1)
                ->where('charts.tractorStatus.online', 1)
                ->where('charts.tractorStatus.offline', 1)
                ->where('charts.tractorStatus.inactive', 0));
    }
}
