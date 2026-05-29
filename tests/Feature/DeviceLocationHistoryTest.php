<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceLocationHistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_loads_the_history_page_without_filters(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $device = Device::create([
            'imei' => '869066063771190',
            'device_name' => 'VL103M-71190',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('devices.history', ['device' => $device], false));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Devices/LocationHistory')
                ->where('device.id', $device->id)
                ->has('locations.data', 0));
    }

    #[Test]
    public function it_returns_paginated_locations_for_the_selected_date_range(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $device = Device::create([
            'imei' => '869066063771191',
            'device_name' => 'VL103M-71191',
            'is_active' => true,
        ]);

        $outsideRange = DeviceLocation::create([
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
            'heartbeat_at' => Carbon::parse('2026-03-28 04:50:46', 'UTC'),
        ]);

        $insideRangeA = DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.200001,
            'lng' => 121.200001,
            'speed' => 8,
            'direction' => 90,
            'status' => 1,
            'acc_status' => 1,
            'gps_num' => 5,
            'pos_type' => 'GPS',
            'heartbeat_at' => Carbon::parse('2026-03-29 04:50:46', 'UTC'),
        ]);

        $insideRangeB = DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.300001,
            'lng' => 121.300001,
            'speed' => 10,
            'direction' => 180,
            'status' => 1,
            'acc_status' => 1,
            'gps_num' => 6,
            'pos_type' => 'GPS',
            'heartbeat_at' => Carbon::parse('2026-03-30 04:50:46', 'UTC'),
        ]);

        $response = $this->actingAs($user)
            ->get(route('devices.history', [
                'device' => $device,
                'from' => '2026-03-29',
                'to' => '2026-03-30',
            ], false));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Devices/LocationHistory')
                ->where('filters.from', '2026-03-29')
                ->where('filters.to', '2026-03-30')
                ->has('locations.data', 2)
                ->where('locations.data.0.id', $insideRangeB->id)
                ->where('locations.data.1.id', $insideRangeA->id));
    }
}
