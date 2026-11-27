<?php

namespace Tests\Unit;

use App\Services\TractorServiceAreaEstimator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TractorServiceAreaEstimatorTest extends TestCase
{
    #[Test]
    public function repeated_field_passes_count_a_single_hectare_for_the_selected_period(): void
    {
        $points = [...$this->fieldPasses(), ...$this->fieldPasses(offset: 3600, segment: 1)];
        $estimate = (new TractorServiceAreaEstimator)->estimate($points, []);

        $this->assertTrue($estimate['available']);
        $this->assertSame(1, $estimate['hectares']);
        $this->assertCount(1, $estimate['cells']);
        $this->assertSame(4, $estimate['cells'][0]['stopCount']);

        $bounds = $estimate['cells'][0]['bounds'];
        $this->assertLessThan(14.49975, $bounds['south']);
        $this->assertGreaterThan(14.50017, $bounds['north']);
        $this->assertLessThan(120.99975, $bounds['west']);
        $this->assertGreaterThan(121.00003, $bounds['east']);
    }

    #[Test]
    public function stationary_and_road_tracks_do_not_count_as_field_work(): void
    {
        $fieldPasses = $this->fieldPasses();
        $parked = array_map(fn (array $point): array => [
            ...$point, 'lat' => 14.49975, 'lng' => 120.99975, 'speed' => 0,
        ], $fieldPasses);
        $straightRoad = array_map(fn (array $point, int $index): array => [
            ...$point, 'lat' => 14.49975, 'lng' => 120.99975 + [0, 0.00018, 0.00018, 0.00036, 0.00054, 0.00054, 0.00072, 0.0008][$index],
        ], $fieldPasses, array_keys($fieldPasses));
        $estimator = new TractorServiceAreaEstimator;

        $this->assertSame(0, $estimator->estimate($parked, [])['hectares']);
        $this->assertSame(0, $estimator->estimate($straightRoad, [])['hectares']);
        $this->assertSame(0, $estimator->estimate($fieldPasses, [[
            'startTime' => $fieldPasses[0]['gpsTime'],
            'endTime' => $fieldPasses[7]['gpsTime'],
        ]])['hectares']);
    }

    #[Test]
    public function low_accuracy_positions_and_long_sampling_intervals_are_not_work_evidence(): void
    {
        $estimator = new TractorServiceAreaEstimator;
        $lbs = array_map(fn (array $point): array => [...$point, 'positionType' => '2'], $this->fieldPasses());

        $this->assertSame(['available' => false, 'hectares' => 0, 'cells' => []], $estimator->estimate($lbs, []));
        $this->assertSame(['available' => false, 'hectares' => 0, 'cells' => []], $estimator->estimate($this->fieldPasses(interval: 180), []));
    }

    private function fieldPasses(int $offset = 0, int $segment = 0, int $interval = 20): array
    {
        $positions = [
            [14.49975, 120.99975, 3],
            [14.49975, 121.00003, 0],
            [14.49975, 121.00003, 0],
            [14.4999, 121.00003, 3],
            [14.50017, 121.00003, 0],
            [14.50017, 121.00003, 0],
            [14.50017, 120.99988, 3],
            [14.50017, 120.99975, 3],
        ];
        $startTime = strtotime('2026-07-22 00:00:00 UTC') + $offset;

        return array_map(fn (array $position, int $index): array => [
            'lat' => $position[0],
            'lng' => $position[1],
            'speed' => $position[2],
            'gpsTime' => gmdate(DATE_ATOM, $startTime + $index * $interval),
            'positionType' => '1',
            'ignitionOn' => true,
            'segment' => $segment,
        ], $positions, array_keys($positions));
    }
}
