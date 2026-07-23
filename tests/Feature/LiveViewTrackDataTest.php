<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Tractor;
use App\Models\User;
use App\Services\Jimi\JimiAuthService;
use App\Services\Jimi\JimiTrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveViewTrackDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function yesterday_uses_the_full_philippine_calendar_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-23 10:00:00', 'Asia/Manila'));
        [$user, $device] = $this->createAccessibleDevice();

        $this->mock(JimiTrackingService::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('fetchTrackData')
                ->once()
                ->with($device->imei, '2026-07-21 16:00:00', '2026-07-22 15:59:59')
                ->andReturn([]);
        });

        $this->actingAs($user)
            ->getJson(route('live-view.track-data', [
                'device_id' => $device->id,
                'period' => 'yesterday',
            ], false))
            ->assertOk()
            ->assertJsonPath('begin_time', '2026-07-21 16:00:00')
            ->assertJsonPath('end_time', '2026-07-22 15:59:59');
    }

    #[Test]
    public function track_points_are_deduplicated_filtered_and_split_at_data_gaps(): void
    {
        [$user, $device] = $this->createAccessibleDevice();

        $this->mock(JimiTrackingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchTrackData')
                ->once()
                ->andReturn([
                    $this->point(14.5000, 121.0000, '2026-07-22 00:00:00', 0),
                    $this->point(14.5000, 121.0000, '2026-07-22 00:00:00', 0),
                    $this->point(0, 0, '2026-07-22 00:01:00', 0),
                    $this->point(14.5009, 121.0000, '2026-07-22 00:02:00', 5),
                    $this->point(14.5018, 121.0000, '2026-07-22 00:30:00', 0),
                    $this->point(20.0000, 125.0000, '2026-07-22 00:31:00', 18),
                ]);
        });

        $response = $this->actingAs($user)->getJson(route('live-view.track-data', [
            'device_id' => $device->id,
            'period' => 'custom',
            'from' => '2026-07-22',
            'to' => '2026-07-22',
        ], false));

        $response->assertOk()
            ->assertJsonPath('track.rawPointCount', 6)
            ->assertJsonPath('track.totalPoints', 3)
            ->assertJsonPath('track.invalidPointCount', 1)
            ->assertJsonPath('track.duplicatePointCount', 1)
            ->assertJsonPath('track.outlierPointCount', 1)
            ->assertJsonPath('track.gapCount', 2)
            ->assertJsonPath('track.segmentCount', 2)
            ->assertJsonPath('track.points.0.segment', 0)
            ->assertJsonPath('track.points.2.segment', 1)
            ->assertJsonPath('track.gaps.0.reason', 'time_gap')
            ->assertJsonPath('track.gaps.1.reason', 'implausible_jump');

        $this->assertEqualsWithDelta(0.1, $response->json('track.distance'), 0.02);
    }

    #[Test]
    public function connected_stationary_points_are_counted_as_a_stop(): void
    {
        [$user, $device] = $this->createAccessibleDevice();

        $this->mock(JimiTrackingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchTrackData')
                ->once()
                ->andReturn([
                    $this->point(14.5, 121.0, '2026-07-22 00:00:00', 0),
                    $this->point(14.5001, 121.0, '2026-07-22 00:06:00', 0),
                ]);
        });

        $this->actingAs($user)
            ->getJson(route('live-view.track-data', [
                'device_id' => $device->id,
                'period' => 'custom',
                'from' => '2026-07-22',
                'to' => '2026-07-22',
            ], false))
            ->assertOk()
            ->assertJsonPath('track.movingDuration', 0)
            ->assertJsonPath('track.idleDuration', 360)
            ->assertJsonPath('track.stopCount', 1);
    }

    #[Test]
    public function partial_jimi_chunk_failures_are_reported_without_discarding_valid_points(): void
    {
        [$user, $device] = $this->createAccessibleDevice();

        $this->mock(JimiTrackingService::class, function (MockInterface $mock): void {
            $call = 0;
            $mock->shouldReceive('fetchTrackData')
                ->times(4)
                ->andReturnUsing(function () use (&$call): array {
                    $call++;

                    if ($call === 2) {
                        throw new \RuntimeException('JIMI daily track quota has been reached.');
                    }

                    if ($call === 4) {
                        return [];
                    }

                    return [
                        $this->point(14.5, 121.0, '2026-07-01 00:00:00', 0),
                        $this->point(14.5009, 121.0, '2026-07-01 00:02:00', 5),
                    ];
                });
        });

        $parameters = [
            'device_id' => $device->id,
            'period' => 'custom',
            'from' => '2026-07-01',
            'to' => '2026-07-04',
        ];

        $this->actingAs($user)
            ->getJson(route('live-view.track-data', $parameters, false))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('partial', true)
            ->assertJsonCount(1, 'warnings')
            ->assertJsonPath('warnings.0.message', 'JIMI daily track quota has been reached.')
            ->assertJsonPath('track.totalPoints', 2);

        $this->actingAs($user)
            ->getJson(route('live-view.track-data', $parameters, false))
            ->assertOk()
            ->assertJsonPath('partial', false)
            ->assertJsonCount(0, 'warnings')
            ->assertJsonPath('track.totalPoints', 2);
    }

    #[Test]
    public function mobile_track_points_use_utc_iso_timestamps_and_philippine_range_metadata(): void
    {
        [$user, $device] = $this->createAccessibleDevice();

        $this->mock(JimiTrackingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchTrackData')
                ->once()
                ->andReturn([
                    $this->point(14.5, 121.0, '2026-07-22 00:00:00', 5),
                    $this->point(14.5009, 121.0, '2026-07-22 00:02:00', 5),
                    ['lng' => 121.0, 'gpsTime' => '2026-07-22 00:02:30', 'gpsSpeed' => 5],
                    $this->point(20.0, 125.0, '2026-07-22 00:03:00', 18),
                    $this->point(14.5018, 121.0, '2026-07-22 00:30:00', 0),
                ]);
        });

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/devices/track-data?'.http_build_query([
            'device_id' => $device->id,
            'period' => 'custom',
            'from' => '2026-07-22',
            'to' => '2026-07-22',
        ]))
            ->assertOk()
            ->assertJsonPath('timezone', 'Asia/Manila')
            ->assertJsonPath('begin_time', '2026-07-21 16:00:00')
            ->assertJsonPath('end_time', '2026-07-22 15:59:59')
            ->assertJsonPath('begin_time_local', '2026-07-22T00:00:00+08:00')
            ->assertJsonPath('end_time_local', '2026-07-22T23:59:59+08:00')
            ->assertJsonPath('points.0.gps_time', '2026-07-22T00:00:00+00:00')
            ->assertJsonPath('points.0.segment', 0)
            ->assertJsonPath('points.2.segment', 1)
            ->assertJsonCount(3, 'points')
            ->assertJsonPath('track.total_points', 3)
            ->assertJsonPath('track.invalid_point_count', 1)
            ->assertJsonPath('track.outlier_point_count', 1)
            ->assertJsonPath('track.segment_count', 2)
            ->assertJsonPath('track.gap_count', 2)
            ->assertJsonPath('track.gaps.0.marker_lat', 14.5009);
    }

    #[Test]
    public function jimi_quota_errors_are_not_treated_as_empty_track_history(): void
    {
        $auth = \Mockery::mock(JimiAuthService::class);
        $auth->shouldReceive('call')->once()->andReturn([
            'code' => 1006,
            'message' => 'Daily limit reached',
        ]);

        $service = new JimiTrackingService($auth);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JIMI daily track quota has been reached.');

        $service->fetchTrackData('869066063779901', '2026-07-22 00:00:00', '2026-07-22 01:00:00');
    }

    #[Test]
    public function custom_track_range_rejects_an_end_date_before_the_start_date(): void
    {
        [$user, $device] = $this->createAccessibleDevice();

        $this->actingAs($user)
            ->getJson(route('live-view.track-data', [
                'device_id' => $device->id,
                'period' => 'custom',
                'from' => '2026-07-22',
                'to' => '2026-07-21',
            ], false))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    /**
     * @return array{User, Device}
     */
    private function createAccessibleDevice(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('super-admin');
        $user->assignRole('super-admin');

        $device = Device::create([
            'imei' => '869066063779901',
            'device_name' => 'TRACK-TEST',
            'is_active' => true,
        ]);

        Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => 'TRACK-TEST',
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.5,
            'lng' => 121.0,
            'heartbeat_at' => now()->utc(),
        ]);

        return [$user, $device];
    }

    /**
     * @return array<string, mixed>
     */
    private function point(float $lat, float $lng, string $gpsTime, float $speed): array
    {
        return compact('lat', 'lng', 'gpsTime', 'speed');
    }
}
