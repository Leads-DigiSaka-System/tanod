<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Services\Jimi\JimiDeviceService;
use App\Services\Jimi\JimiTrackingService;
use App\Services\SeasonalServiceArea;
use App\Services\TractorServiceAreaEstimator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeasonalServiceAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_philippine_days_are_collected_once_and_shared_cells_are_deduplicated(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-03 00:05:00', 'Asia/Manila'));
        $first = $this->trackedDevice('869066063770001');
        $second = $this->trackedDevice('869066063770002');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('super-admin'));

        $tracking = $this->mock(JimiTrackingService::class);
        $tracking->shouldReceive('fetchParkingReport')->never();
        $points = $this->shortFieldPasses();
        foreach ([$first, $second] as $device) {
            $tracking->shouldReceive('fetchTrackData')
                ->once()
                ->with($device->imei, '2026-09-30 16:00:00', '2026-10-02 15:59:59')
                ->andReturn($points);
        }

        $this->mock(JimiDeviceService::class)->shouldReceive('fetchLiveLocations')->andReturn([]);
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seasonalServiceArea.hectares', null)
                ->where('seasonalServiceArea.checkedTractorDays', 0));

        $this->artisan('jimi:sync-service-area', ['--limit' => 2])->assertSuccessful();
        $this->artisan('jimi:sync-service-area', ['--limit' => 2])->assertSuccessful();

        $this->assertDatabaseHas('tractor_service_area_days', [
            'device_id' => $first->id,
            'local_date' => '2026-10-02',
            'has_usable_gps' => true,
        ]);
        $this->assertDatabaseCount('tractor_service_area_days', 4);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seasonalServiceArea.startsOn', '2026-10-01')
                ->where('seasonalServiceArea.endsOn', '2027-01-31')
                ->where('seasonalServiceArea.hectares', 0.25)
                ->where('seasonalServiceArea.checkedTractorDays', 4)
                ->where('seasonalServiceArea.expectedTractorDays', 4)
                ->where('seasonalServiceArea.months.0.hectares', 0.25)
                ->where('seasonalServiceArea.months.1.upcoming', true));
    }

    public function test_dry_season_keeps_a_cell_in_its_first_detected_month_and_excludes_february(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-02-01 00:01:00', 'Asia/Manila'));
        $device = $this->trackedDevice('869066063770003');
        $version = app(TractorServiceAreaEstimator::class)->version();
        foreach ([
            ['2026-10-31', ['200:300']],
            ['2026-11-01', ['200:300', '200:301']],
            ['2027-01-31', ['202:301']],
            ['2027-02-01', ['204:305']],
        ] as [$date, $cells]) {
            DB::table('tractor_service_area_days')->insert([
                'device_id' => $device->id,
                'local_date' => $date,
                'algorithm' => $version,
                'has_usable_gps' => true,
                'cell_ids' => json_encode($cells, JSON_THROW_ON_ERROR),
            ]);
        }

        $summary = app(SeasonalServiceArea::class)->summary();

        $this->assertTrue($summary['closed']);
        $this->assertSame('2027-10-01', $summary['nextStartsOn']);
        $this->assertSame(0.75, $summary['hectares']);
        $this->assertSame([0.25, 0.25, null, 0.25], array_column($summary['months'], 'hectares'));

        $this->travelTo(CarbonImmutable::parse('2027-10-01 00:01:00', 'Asia/Manila'));
        $next = app(SeasonalServiceArea::class)->summary();
        $this->assertSame('2027-10-01', $next['startsOn']);
        $this->assertNull($next['hectares']);
        $this->assertSame(0, $next['expectedTractorDays']);
    }

    public function test_provider_quota_pauses_collection_without_discarding_processed_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-03 07:30:00', 'Asia/Manila'));
        $first = $this->trackedDevice('869066063770004');
        $second = $this->trackedDevice('869066063770005');
        $tracking = $this->mock(JimiTrackingService::class);
        $tracking->shouldReceive('fetchTrackData')->withArgs(fn ($imei) => $imei === $first->imei)->once()->andReturn([]);
        $tracking->shouldReceive('fetchTrackData')->withArgs(fn ($imei) => $imei === $second->imei)
            ->once()->andThrow(new \RuntimeException('Daily quota reached.', JimiTrackingService::TRACK_QUOTA_CODE));
        $tracking->shouldReceive('fetchTrackData')->withArgs(fn ($imei) => $imei === $second->imei)->once()->andReturn([]);

        $this->artisan('jimi:sync-service-area')->assertSuccessful();
        $this->artisan('jimi:sync-service-area')->assertSuccessful();
        $this->assertDatabaseCount('tractor_service_area_days', 2);
        $summary = app(SeasonalServiceArea::class)->summary();
        $this->assertSame(2, $summary['checkedTractorDays']);
        $this->assertNull($summary['hectares']);

        $this->travel(31)->minutes();
        $this->artisan('jimi:sync-service-area')->assertSuccessful();
        $this->assertDatabaseCount('tractor_service_area_days', 4);
    }

    public function test_fleet_area_is_exposed_only_with_admin_dashboard_statistics(): void
    {
        $farmer = User::factory()->create(['is_active' => true]);
        $farmer->assignRole(Role::findOrCreate('farmer'));

        $this->actingAs($farmer)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('seasonalServiceArea'));
    }

    private function trackedDevice(string $imei): Device
    {
        $device = Device::create(['imei' => $imei, 'device_name' => 'Field tractor', 'is_active' => true]);
        $device->tractor()->create(['imei' => $imei, 'no_plate' => $imei, 'is_active' => true]);
        $device->locations()->create(['imei' => $imei, 'lat' => 14.49975, 'lng' => 120.99975, 'heartbeat_at' => now()]);

        return $device;
    }

    private function shortFieldPasses(): array
    {
        $start = CarbonImmutable::parse('2026-10-01 16:30:00', 'UTC');
        $legs = [
            [14.49975, 120.99975, 0], [14.499895, 120.99975, 16],
            [14.499895, 120.999783, 20], [14.49975, 120.999783, 36],
            [14.49975, 120.999816, 40], [14.499895, 120.999816, 56],
        ];

        return array_map(fn (array $leg): array => [
            'lat' => $leg[0], 'lng' => $leg[1], 'gpsTime' => $start->addSeconds($leg[2])->toDateTimeString(),
            'gpsSpeed' => 3.6, 'posType' => 1,
        ], $legs);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn (string $date, string $format): string => substr($date, 0, 7), 2);
        }
    }
}
