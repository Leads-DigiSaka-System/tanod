<?php

namespace Tests\Feature\Api;

use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\DeviceTrackRecord;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tractor $tractor;

    private string $integrationToken;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super-admin');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');
        $this->integrationToken = $this->admin
            ->createToken('Partner System', ['integration:read'])
            ->plainTextToken;

        $device = Device::create([
            'imei' => '869066063771910',
            'device_name' => 'North GPS',
            'device_model' => 'VL103M',
            'is_active' => true,
        ]);

        DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.5995,
            'lng' => 120.9842,
            'speed' => 12.4,
            'direction' => 92,
            'status' => 1,
            'acc_status' => 1,
            'gps_num' => 8,
            'pos_type' => 'gps',
            'heartbeat_at' => now()->subSeconds(8),
        ]);

        $this->tractor = Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'name' => 'Tractor North 1',
            'no_plate' => 'TRC-042',
            'engine_no' => 'EN-4221',
            'chassis_no' => 'CH-9012',
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        Alert::create([
            'device_id' => $device->id,
            'tractor_id' => $this->tractor->id,
            'type' => 'speed',
            'title' => 'Speed threshold exceeded',
            'message' => 'Tractor reached 46 km/h.',
            'meta' => ['speed_kph' => 46],
            'is_acknowledged' => false,
        ]);
    }

    #[Test]
    public function integration_endpoints_require_a_dedicated_integration_token(): void
    {
        $this->getJson('/api/integration/v1/tractors')
            ->assertUnauthorized();

        $mobileToken = $this->admin->createToken('Mobile Login', ['*'])->plainTextToken;

        $this->withToken($mobileToken)
            ->getJson('/api/integration/v1/tractors')
            ->assertForbidden()
            ->assertJsonPath('message', 'This endpoint requires a third-party integration token.');
    }

    #[Test]
    public function a_same_origin_session_request_does_not_crash_the_integration_rate_limiter(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/integration/v1/summary')
            ->assertForbidden()
            ->assertJsonPath('message', 'This endpoint requires a third-party integration token.');
    }

    #[Test]
    public function partners_can_list_and_view_complete_tractor_details(): void
    {
        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors?search=TRC-042&active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->tractor->id)
            ->assertJsonPath('data.0.device.online', true)
            ->assertJsonPath('meta.per_page', 25);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id)
            ->assertOk()
            ->assertJsonPath('data.identifiers.engine_number', 'EN-4221')
            ->assertJsonPath('data.machine.model', 'L4708')
            ->assertJsonPath('data.active', true);
    }

    #[Test]
    public function partners_can_poll_a_single_tractor_location(): void
    {
        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/location')
            ->assertOk()
            ->assertJsonPath('data.tractor.id', $this->tractor->id)
            ->assertJsonPath('data.position.latitude', 14.5995)
            ->assertJsonPath('data.position.longitude', 120.9842)
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.stale', false);
    }

    #[Test]
    public function partners_can_filter_single_tractor_and_general_alerts(): void
    {
        Alert::create([
            'type' => 'maintenance_due',
            'title' => 'Unrelated alert',
            'is_acknowledged' => true,
        ]);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/alerts?type=speed&acknowledged=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'speed')
            ->assertJsonPath('data.0.tractor.id', $this->tractor->id);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/alerts?tractor_id='.$this->tractor->id.'&acknowledged=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Speed threshold exceeded');
    }

    #[Test]
    public function integration_query_parameters_are_validated(): void
    {
        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/alerts?per_page=101&from=2026-07-22&to=2026-07-21')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page', 'to']);
    }

    #[Test]
    public function partners_can_read_fleet_summary_and_alert_types(): void
    {
        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/summary')
            ->assertOk()
            ->assertJsonPath('data.tractors.total', 1)
            ->assertJsonPath('data.tractors.online', 1)
            ->assertJsonPath('data.alerts.unacknowledged', 1)
            ->assertJsonPath('data.maintenance.open', 0);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/alert-types')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'speed')
            ->assertJsonPath('data.0.total', 1)
            ->assertJsonPath('data.0.unacknowledged', 1);
    }

    #[Test]
    public function partners_can_poll_a_map_ready_live_view_of_all_tractors(): void
    {
        Tractor::create([
            'name' => 'Tractor Without GPS',
            'no_plate' => 'TRC-OFFLINE',
            'brand' => 'Kubota',
            'model' => 'M7040',
            'is_active' => true,
        ]);

        $response = $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/live/tractors?stale_after_seconds=300');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tractor.id', $this->tractor->id)
            ->assertJsonPath('data.0.status.online', true)
            ->assertJsonPath('data.0.status.moving', true)
            ->assertJsonPath('data.0.unacknowledged_alerts', 1)
            ->assertJsonPath('meta.returned', 1)
            ->assertJsonPath('meta.online', 1)
            ->assertJsonPath('meta.moving', 1)
            ->assertJsonPath('meta.recommended_poll_interval_seconds', 15);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/live/tractors?include_without_location=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/live/tractors?changed_since='.urlencode(now()->addMinute()->toIso8601String()))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function partners_can_read_location_and_maintenance_history(): void
    {
        DeviceLocation::create([
            'device_id' => $this->tractor->device_id,
            'imei' => $this->tractor->imei,
            'lat' => 14.6001,
            'lng' => 120.9850,
            'speed' => 8.2,
            'heartbeat_at' => now()->subHour(),
        ]);

        $maintenance = Maintenance::create([
            'tractor_id' => $this->tractor->id,
            'maintenance_date' => now()->toDateString(),
            'description' => '250-hour preventive maintenance',
            'cost' => 4800,
            'hours_at_maintenance' => 251.2,
            'status' => 'completed',
        ]);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/location-history?per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.latitude', 14.5995)
            ->assertJsonPath('meta.per_page', 10);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/maintenance?status=completed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $maintenance->id)
            ->assertJsonPath('data.0.description', '250-hour preventive maintenance')
            ->assertJsonPath('data.0.cost', 4800);

    }

    #[Test]
    public function partners_can_read_mileage_summaries_and_trip_track_data(): void
    {
        DeviceTrackRecord::create([
            'device_id' => $this->tractor->device_id,
            'imei' => $this->tractor->imei,
            'start_lat' => 14.5995,
            'start_lng' => 120.9842,
            'end_lat' => 14.6500,
            'end_lng' => 121.0100,
            'mileage' => 12.5,
            'run_time_seconds' => 3600,
            'max_speed' => 40,
            'start_time' => now()->startOfDay()->addHours(8),
            'end_time' => now()->startOfDay()->addHours(9),
        ]);

        DeviceTrackRecord::create([
            'device_id' => $this->tractor->device_id,
            'imei' => $this->tractor->imei,
            'mileage' => 7.5,
            'run_time_seconds' => 1800,
            'max_speed' => 30,
            'start_time' => now()->subWeek(),
            'end_time' => now()->subWeek()->addMinutes(30),
        ]);

        $today = now()->toDateString();

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/mileage?from='.$today.'&to='.$today)
            ->assertOk()
            ->assertJsonPath('data.summary.mileage_km', 12.5)
            ->assertJsonPath('data.summary.runtime_seconds', 3600)
            ->assertJsonPath('data.summary.runtime_hours', 1)
            ->assertJsonPath('data.summary.trips', 1)
            ->assertJsonPath('data.all_time.odometer_km', 20)
            ->assertJsonCount(1, 'data.daily')
            ->assertJsonPath('data.daily.0.date', $today);

        $this->withToken($this->integrationToken)
            ->getJson('/api/integration/v1/tractors/'.$this->tractor->id.'/track-data?per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.mileage_km', 12.5)
            ->assertJsonPath('data.0.runtime_hours', 1)
            ->assertJsonPath('data.0.start.latitude', 14.5995)
            ->assertJsonPath('meta.per_page', 10);
    }
}
