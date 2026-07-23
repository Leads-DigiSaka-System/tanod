<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceShare;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use App\Services\Jimi\JimiAuthService;
use App\Services\Jimi\JimiDeviceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function authorized_roles_can_access_the_live_view_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Role::findOrCreate('tps');
        $user->assignRole('tps');

        $response = $this->actingAs($user)->get(route('live-view.index', absolute: false));

        $response->assertOk();
    }

    #[Test]
    public function disallowed_roles_cannot_access_live_view_routes(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Role::findOrCreate('fca');
        $user->assignRole('fca');

        $response = $this->actingAs($user)->get(route('live-view.index', absolute: false));

        $response->assertForbidden();
    }

    #[Test]
    public function share_data_uses_the_configured_online_threshold_without_treating_stale_speed_as_movement(): void
    {
        config()->set('jimi.online_threshold_minutes', 10);

        $device = Device::create([
            'imei' => '869066063771183',
            'device_name' => 'VL103M-71183',
            'is_active' => true,
        ]);

        Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => 'VL103M-71183',
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.100001,
            'lng' => 121.100001,
            'speed' => 0,
            'direction' => 0,
            'status' => 0,
            'acc_status' => 0,
            'gps_num' => 4,
            'pos_type' => 'GPS',
            'heartbeat_at' => Carbon::parse('2026-03-30 04:50:46', 'UTC'),
            'raw_data' => ['source' => 'stale-db'],
        ]);

        $share = DeviceShare::create([
            'token' => str_repeat('a', 48),
            'device_id' => $device->id,
            'imei' => $device->imei,
            'device_name' => 'VL103M-71183',
            'expires_at' => now()->addHour(),
        ]);

        $liveHeartbeat = now()->utc()->subMinutes(9)->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($device, $liveHeartbeat): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $device->imei => [
                        'imei' => $device->imei,
                        'hbTime' => $liveHeartbeat->format('Y-m-d H:i:s'),
                        'lat' => 12.900274,
                        'lng' => 124.032862,
                        'speed' => 14,
                        'direction' => 180,
                        'accStatus' => 1,
                        'gpsNum' => 7,
                        'posType' => 'GPS',
                        'mileage' => 1234.56,
                    ],
                ]);
        });

        $response = $this->getJson(route('share.data', ['token' => $share->token], false));

        $response->assertOk()
            ->assertJsonPath('expired', false)
            ->assertJsonPath('device.status', 'idling')
            ->assertJsonPath('device.heartbeat_at', $liveHeartbeat->toIso8601String())
            ->assertJsonPath('device.lat', 12.900274)
            ->assertJsonPath('device.lng', 124.032862)
            ->assertJsonPath('device.tractor.no_plate', 'VL103M-71183');
    }

    #[Test]
    public function tps_users_only_receive_devices_from_their_assigned_groups(): void
    {
        $user = $this->createTpsUser();
        $visibleDevice = $this->createGroupedDevice('869066063771184', 'VL103M-71184', true, $user);
        $hiddenDevice = $this->createGroupedDevice('869066063771185', 'VL103M-71185');

        $liveHeartbeat = now()->utc()->subMinutes(2)->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($visibleDevice, $hiddenDevice, $liveHeartbeat): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $visibleDevice->imei => $this->livePayload($visibleDevice, $liveHeartbeat, 18),
                    $hiddenDevice->imei => $this->livePayload($hiddenDevice, $liveHeartbeat, 12),
                ]);
        });

        $response = $this->actingAs($user)->getJson(route('live-view.locations', absolute: false));

        $response->assertOk()
            ->assertJsonCount(1, 'devices')
            ->assertJsonPath('devices.0.id', $visibleDevice->id)
            ->assertJsonMissing([
                'id' => $hiddenDevice->id,
                'imei' => $hiddenDevice->imei,
            ]);
    }

    #[Test]
    public function live_locations_use_jimi_connectivity_and_acc_status_instead_of_stale_speed(): void
    {
        $user = $this->createTpsUser();
        $parkedDevice = $this->createGroupedDevice('869066063771187', 'VL103M-71187', true, $user);
        $offlineDevice = $this->createGroupedDevice('869066063771188', 'VL103M-71188', true, $user);
        $movingDevice = $this->createGroupedDevice('869066063771189', 'VL103M-71189', true, $user);
        $idlingDevice = $this->createGroupedDevice('869066060243400', 'VL103M-43400', true, $user);
        $liveHeartbeat = now()->utc()->subMinute()->startOfSecond();
        $staleGpsFix = now()->utc()->subMinutes(66)->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($parkedDevice, $offlineDevice, $movingDevice, $idlingDevice, $liveHeartbeat, $staleGpsFix): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $parkedDevice->imei => [
                        ...$this->livePayload($parkedDevice, $liveHeartbeat, 14),
                        'status' => 1,
                        'accStatus' => 0,
                    ],
                    $offlineDevice->imei => [
                        ...$this->livePayload($offlineDevice, $liveHeartbeat, 14),
                        'status' => 0,
                        'accStatus' => 1,
                    ],
                    $movingDevice->imei => [
                        ...$this->livePayload($movingDevice, $liveHeartbeat, 14),
                        'status' => 1,
                        'accStatus' => 1,
                        'gpsTime' => $liveHeartbeat->format('Y-m-d H:i:s'),
                    ],
                    $idlingDevice->imei => [
                        ...$this->livePayload($idlingDevice, $liveHeartbeat, 18),
                        'status' => 1,
                        'accStatus' => 1,
                        'gpsTime' => $staleGpsFix->format('Y-m-d H:i:s'),
                    ],
                ]);
        });

        $response = $this->actingAs($user)->getJson(route('live-view.locations', absolute: false));

        $response->assertOk();

        $statusesByDeviceId = collect($response->json('devices'))->pluck('status', 'id');

        $this->assertSame('parked', $statusesByDeviceId[$parkedDevice->id]);
        $this->assertSame('offline', $statusesByDeviceId[$offlineDevice->id]);
        $this->assertSame('moving', $statusesByDeviceId[$movingDevice->id]);
        $this->assertSame('idling', $statusesByDeviceId[$idlingDevice->id]);
        $response->assertJsonPath('devices.3.gps_time', $staleGpsFix->toIso8601String());
        $response->assertJsonPath('devices.3.gps_minutes_ago', 66);
    }

    #[Test]
    public function live_locations_tolerate_malformed_jimi_timestamps(): void
    {
        $user = $this->createTpsUser();
        $device = $this->createGroupedDevice('869066063771193', 'VL103M-71193', true, $user);

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('fetchLiveLocations')
                ->once()
                ->andReturn([
                    $device->imei => [
                        'imei' => $device->imei,
                        'hbTime' => 'not-a-date',
                        'gpsTime' => 'also-not-a-date',
                        'lat' => 12.900274,
                        'lng' => 124.032862,
                        'speed' => 18,
                        'status' => 1,
                        'accStatus' => 0,
                    ],
                ]);
        });

        $this->actingAs($user)
            ->getJson(route('live-view.locations', absolute: false))
            ->assertOk()
            ->assertJsonPath('devices.0.status', 'parked')
            ->assertJsonPath('devices.0.heartbeat_at', null)
            ->assertJsonPath('devices.0.gps_time', null)
            ->assertJsonPath('devices.0.gps_minutes_ago', null);
    }

    #[Test]
    public function follow_requests_only_the_selected_device_from_the_jimi_service(): void
    {
        $user = $this->createTpsUser();
        $device = $this->createGroupedDevice('869066063771190', 'VL103M-71190', true, $user);
        $liveHeartbeat = now()->utc()->subMinute()->startOfSecond();

        $this->mock(JimiDeviceService::class, function (MockInterface $mock) use ($device, $liveHeartbeat): void {
            $mock->shouldReceive('fetchDeviceLocationRealtime')
                ->once()
                ->with($device->imei)
                ->andReturn([
                    ...$this->livePayload($device, $liveHeartbeat, 14),
                    'status' => 1,
                    'accStatus' => 0,
                ]);
            $mock->shouldNotReceive('fetchLocationsRealtime');
        });

        $response = $this->actingAs($user)->getJson(route('live-view.follow', ['device' => $device], false));

        $response->assertOk()
            ->assertJsonPath('device.id', $device->id)
            ->assertJsonPath('device.status', 'parked')
            ->assertJsonPath('device.acc_status', false)
            ->assertJsonPath('device.speed', 14);
    }

    #[Test]
    public function selected_device_fetch_uses_the_supported_realtime_location_list(): void
    {
        $imei = '869066063771192';
        $location = [
            'imei' => $imei,
            'status' => 1,
            'accStatus' => 0,
            'speed' => 0,
        ];
        $auth = \Mockery::mock(JimiAuthService::class);
        $auth->shouldReceive('call')
            ->once()
            ->with('jimi.user.device.location.list', [
                'target' => config('jimi.user_id'),
                'map_type' => config('jimi.map_type', 'WGS84'),
            ])
            ->andReturn([
                'code' => 0,
                'result' => [$location],
            ]);

        $service = new JimiDeviceService($auth);

        $this->assertSame($location, $service->fetchDeviceLocationRealtime($imei));
    }

    #[Test]
    public function jimi_failures_are_not_reported_as_offline_tractors(): void
    {
        $user = $this->createTpsUser();
        $device = $this->createGroupedDevice('869066063771191', 'VL103M-71191', true, $user);

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLiveLocations')->once()->andReturn([]);
            $mock->shouldReceive('fetchDeviceLocationRealtime')->once()->andReturnNull();
        });

        $this->actingAs($user)
            ->getJson(route('live-view.locations', absolute: false))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Live locations are temporarily unavailable.');

        $this->actingAs($user)
            ->getJson(route('live-view.follow', ['device' => $device], false))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Live location is temporarily unavailable.');
    }

    #[Test]
    public function tps_users_cannot_follow_or_share_devices_outside_their_groups(): void
    {
        $user = $this->createTpsUser();
        $hiddenDevice = $this->createGroupedDevice('869066063771186', 'VL103M-71186');

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('fetchDeviceLocationRealtime');
        });

        $this->actingAs($user)
            ->getJson(route('live-view.follow', ['device' => $hiddenDevice], false))
            ->assertNotFound();

        $this->actingAs($user)
            ->postJson(route('live-view.share', absolute: false), [
                'device_id' => $hiddenDevice->id,
                'duration' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('device_shares', 0);
    }

    private function createTpsUser(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Role::findOrCreate('tps');
        $user->assignRole('tps');

        return $user;
    }

    private function createGroupedDevice(string $imei, string $plate, bool $assignToUser = false, ?User $user = null): Device
    {
        $device = Device::create([
            'imei' => $imei,
            'device_name' => $plate,
            'is_active' => true,
        ]);

        $tractor = Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => $plate,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        $group = TractorGroup::create([
            'name' => 'Group '.$plate,
            'is_active' => true,
        ]);

        $tractor->groups()->attach($group->id);

        if ($assignToUser && $user) {
            $group->users()->attach($user->id, ['role' => 'tps']);
        }

        DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 12.900274,
            'lng' => 124.032862,
            'speed' => 0,
            'direction' => 0,
            'status' => 1,
            'acc_status' => 0,
            'gps_num' => 7,
            'pos_type' => 'GPS',
            'heartbeat_at' => now()->utc(),
            'raw_data' => ['source' => 'test-fixture'],
        ]);

        return $device->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function livePayload(Device $device, Carbon $heartbeatAt, int $speed): array
    {
        return [
            'imei' => $device->imei,
            'hbTime' => $heartbeatAt->format('Y-m-d H:i:s'),
            'lat' => 12.900274,
            'lng' => 124.032862,
            'speed' => $speed,
            'direction' => 180,
            'accStatus' => 1,
            'gpsNum' => 7,
            'posType' => 'GPS',
            'mileage' => 1234.56,
        ];
    }
}
