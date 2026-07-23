<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Tractor;
use App\Models\User;
use App\Services\Jimi\JimiDeviceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeviceLiveLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function mobile_live_locations_ignore_stale_speed_when_the_gps_fix_is_old(): void
    {
        $user = $this->createAdminUser();
        $device = $this->createDevice('869066060243400');
        $heartbeatAt = now()->utc()->subMinute()->startOfSecond();
        $gpsAt = now()->utc()->subMinutes(66)->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($device, $heartbeatAt, $gpsAt): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $device->imei => $this->livePayload($device, $heartbeatAt, $gpsAt),
                ]);
        });

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/devices/live-locations');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locations.0.device_id', $device->id)
            ->assertJsonPath('locations.0.status', 1)
            ->assertJsonPath('locations.0.live_status', 'idling')
            ->assertJsonPath('locations.0.acc_status', 1)
            ->assertJsonPath('locations.0.speed', 18)
            ->assertJsonPath('locations.0.heartbeat_at', $heartbeatAt->format('Y-m-d H:i:s'))
            ->assertJsonPath('locations.0.heartbeat_at_iso', $heartbeatAt->toIso8601String())
            ->assertJsonPath('locations.0.gps_time', $gpsAt->toIso8601String())
            ->assertJsonPath('locations.0.gps_minutes_ago', 66);
    }

    #[Test]
    public function mobile_live_locations_expose_all_four_canonical_states(): void
    {
        $user = $this->createAdminUser();
        $movingDevice = $this->createDevice('869066060243402');
        $idlingDevice = $this->createDevice('869066060243403');
        $parkedDevice = $this->createDevice('869066060243404');
        $offlineDevice = $this->createDevice('869066060243405');
        $heartbeatAt = now()->utc()->subMinute()->startOfSecond();
        $freshGpsAt = now()->utc()->subMinute()->startOfSecond();
        $staleGpsAt = now()->utc()->subMinutes(10)->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($movingDevice, $idlingDevice, $parkedDevice, $offlineDevice, $heartbeatAt, $freshGpsAt, $staleGpsAt): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $movingDevice->imei => $this->livePayload($movingDevice, $heartbeatAt, $freshGpsAt),
                    $idlingDevice->imei => $this->livePayload($idlingDevice, $heartbeatAt, $staleGpsAt),
                    $parkedDevice->imei => [
                        ...$this->livePayload($parkedDevice, $heartbeatAt, $freshGpsAt),
                        'accStatus' => 0,
                    ],
                    $offlineDevice->imei => [
                        ...$this->livePayload($offlineDevice, $heartbeatAt, $freshGpsAt),
                        'status' => 0,
                    ],
                ]);
        });

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/devices/live-locations');
        $statuses = collect($response->json('locations'))->pluck('live_status', 'imei');

        $response->assertOk();
        $this->assertSame('moving', $statuses[$movingDevice->imei]);
        $this->assertSame('idling', $statuses[$idlingDevice->imei]);
        $this->assertSame('parked', $statuses[$parkedDevice->imei]);
        $this->assertSame('offline', $statuses[$offlineDevice->imei]);
    }

    #[Test]
    public function mobile_live_locations_tolerate_malformed_jimi_timestamps(): void
    {
        $user = $this->createAdminUser();
        $device = $this->createDevice('869066060243406');

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $device->imei => [
                        'imei' => $device->imei,
                        'lat' => 15.062024,
                        'lng' => 120.765529,
                        'speed' => 18,
                        'status' => 1,
                        'accStatus' => 0,
                        'hbTime' => 'not-a-date',
                        'gpsTime' => 'also-not-a-date',
                    ],
                ]);
        });

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/devices/live-locations')
            ->assertOk()
            ->assertJsonPath('locations.0.live_status', 'parked')
            ->assertJsonPath('locations.0.heartbeat_at', 'not-a-date')
            ->assertJsonPath('locations.0.heartbeat_at_iso', null)
            ->assertJsonPath('locations.0.gps_time', null)
            ->assertJsonPath('locations.0.gps_minutes_ago', null);
    }

    #[Test]
    public function mobile_live_endpoints_preserve_v1_outage_shapes(): void
    {
        $user = $this->createAdminUser();
        $device = $this->createDevice('869066060243401');

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLiveLocations')->once()->andReturn([]);
            $mock->shouldReceive('fetchDeviceLocationRealtime')->once()->andReturnNull();
        });

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/devices/live-locations')
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('locations', [])
            ->assertJsonPath('message', 'Live locations are temporarily unavailable.');

        $this->getJson("/api/v1/devices/follow/{$device->id}")
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('location', null)
            ->assertJsonPath('message', 'Live location is temporarily unavailable.');
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('super-admin');
        $user->assignRole('super-admin');

        return $user;
    }

    private function createDevice(string $imei): Device
    {
        $device = Device::create([
            'imei' => $imei,
            'device_name' => 'Mobile Live Tractor',
            'is_active' => true,
        ]);

        Tractor::create([
            'device_id' => $device->id,
            'imei' => $imei,
            'no_plate' => 'MOBILE-'.$imei,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        return $device;
    }

    /**
     * @return array<string, mixed>
     */
    private function livePayload(Device $device, Carbon $heartbeatAt, Carbon $gpsAt): array
    {
        return [
            'imei' => $device->imei,
            'lat' => 15.062024,
            'lng' => 120.765529,
            'speed' => 18,
            'direction' => 231,
            'status' => 1,
            'accStatus' => 1,
            'hbTime' => $heartbeatAt->format('Y-m-d H:i:s'),
            'gpsTime' => $gpsAt->format('Y-m-d H:i:s'),
        ];
    }
}
