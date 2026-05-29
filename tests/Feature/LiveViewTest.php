<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceShare;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
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
    public function share_data_uses_the_live_location_payload_with_the_ten_minute_threshold(): void
    {
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
            ->assertJsonPath('device.status', 'moving')
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
    public function tps_users_cannot_follow_or_share_devices_outside_their_groups(): void
    {
        $user = $this->createTpsUser();
        $hiddenDevice = $this->createGroupedDevice('869066063771186', 'VL103M-71186');

        $this->mock(JimiDeviceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('fetchLocationsRealtime');
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
