<?php

namespace Tests\Unit;

use App\Services\TractorServiceAreaEstimator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TractorServiceAreaEstimatorTest extends TestCase
{
    #[Test]
    public function continuous_field_passes_count_a_single_hectare_per_cell(): void
    {
        $points = $this->track($this->fieldPasses());
        $secondVisit = $this->track($this->fieldPasses(), offset: 3600, segment: 1);
        $estimate = (new TractorServiceAreaEstimator)->estimate([...$points, ...$secondVisit]);

        $this->assertTrue($estimate['available']);
        $this->assertSame(1, $estimate['hectares']);
        $this->assertCount(1, $estimate['cells']);

        $bounds = $estimate['cells'][0]['bounds'];
        $this->assertLessThan(14.49975, $bounds['south']);
        $this->assertGreaterThan(14.50017, $bounds['north']);
        $this->assertLessThan(120.99975, $bounds['west']);
        $this->assertGreaterThan(121.00000, $bounds['east']);
    }

    #[Test]
    public function densely_sampled_movement_still_counts_when_single_steps_are_small(): void
    {
        $points = $this->track($this->fieldPasses(), sampleMetres: 2);

        $this->assertSame(1, (new TractorServiceAreaEstimator)->estimate($points)['hectares']);
    }

    #[Test]
    public function slightly_curved_and_staggered_passes_can_count(): void
    {
        $path = [
            [0, 0], [-2, 22], [1, 39], [4, 56],
            [13, 58], [11, 43], [10, 22], [9, 8],
            [21, 11], [19, 24], [19, 39], [22, 57],
        ];

        $this->assertSame(1, (new TractorServiceAreaEstimator)->estimate($this->track($path))['hectares']);
    }

    #[Test]
    public function connecting_travel_between_two_worked_patches_does_not_add_cells(): void
    {
        $path = [
            [0, -160],
            ...$this->fieldPasses(),
            ...$this->fieldPasses(east: 330),
        ];
        $estimate = (new TractorServiceAreaEstimator)->estimate($this->track($path));

        $this->assertSame(2, $estimate['hectares']);
        $cells = array_column($estimate['cells'], 'bounds');
        usort($cells, fn (array $left, array $right) => $left['west'] <=> $right['west']);
        $this->assertSame($cells[0]['south'], $cells[1]['south']);
        $this->assertGreaterThan($cells[0]['east'], $cells[1]['west']);
    }

    #[Test]
    public function passes_can_cross_cell_boundaries_without_breaking_field_work(): void
    {
        $estimator = new TractorServiceAreaEstimator;
        $crossing = $estimator->estimate($this->track($this->fieldPasses(length: 140)));
        $smallOverlap = $estimator->estimate($this->track($this->fieldPasses(length: 85)));

        $this->assertSame(2, $crossing['hectares']);
        $this->assertSame(1, $smallOverlap['hectares']);
    }

    #[Test]
    public function parked_drift_road_bends_retraces_and_a_single_turnaround_do_not_count(): void
    {
        $paths = [
            'parked jitter' => [[0, 0], [5, 5], [0, 9], [8, 2], [0, 0], [6, 8], [0, 0]],
            'road bend' => [[0, 0], [0, 55], [52, 55], [53, 0]],
            'two-lane retrace' => [[0, 0], [0, 54], [9, 54], [9, 0], [0, 0], [0, 54], [9, 54], [9, 0]],
            'single U-turn' => array_slice($this->fieldPasses(), 0, 4),
        ];
        $estimator = new TractorServiceAreaEstimator;

        foreach ($paths as $description => $path) {
            $estimate = $estimator->estimate($this->track($path));
            $this->assertTrue($estimate['available'], $description);
            $this->assertSame(0, $estimate['hectares'], $description);
        }

        $fast = array_map(fn (array $point): array => [...$point, 'speed' => 18], $this->track($this->fieldPasses()));
        $this->assertSame(0, $estimator->estimate($fast)['hectares']);
    }

    #[Test]
    public function passes_do_not_combine_across_reporting_gaps_or_track_segments(): void
    {
        $points = $this->track($this->fieldPasses(), sampleMetres: 60);
        $splitSegments = array_map(fn (array $point, int $index): array => [
            ...$point, 'segment' => $index < 4 ? 0 : 1,
        ], $points, array_keys($points));
        $reportingGap = array_map(fn (array $point, int $index): array => [
            ...$point, 'gpsTime' => gmdate(DATE_ATOM, strtotime($point['gpsTime']) + ($index < 4 ? 0 : 180)),
        ], $points, array_keys($points));
        $estimator = new TractorServiceAreaEstimator;

        $this->assertSame(0, $estimator->estimate($splitSegments)['hectares']);
        $this->assertSame(0, $estimator->estimate($reportingGap)['hectares']);
    }

    #[Test]
    public function low_accuracy_positions_and_long_sampling_intervals_are_not_work_evidence(): void
    {
        $points = $this->track($this->fieldPasses());
        $lbs = array_map(fn (array $point): array => [...$point, 'positionType' => '2'], $points);
        $sparse = array_map(fn (array $point, int $index): array => [
            ...$point, 'gpsTime' => gmdate(DATE_ATOM, strtotime($point['gpsTime']) + $index * 180),
        ], $points, array_keys($points));
        $estimator = new TractorServiceAreaEstimator;

        $this->assertSame(['available' => false, 'hectares' => 0, 'cells' => []], $estimator->estimate($lbs));
        $this->assertSame(['available' => false, 'hectares' => 0, 'cells' => []], $estimator->estimate($sparse));
    }

    private function fieldPasses(int $east = 0, int $length = 54): array
    {
        return [
            [$east, 0], [$east, $length],
            [$east + 9, $length], [$east + 9, 0],
            [$east + 18, 0], [$east + 18, $length],
            [$east + 27, $length], [$east + 27, 0],
        ];
    }

    private function track(array $path, int $sampleMetres = 18, int $offset = 0, int $segment = 0): array
    {
        $time = strtotime('2026-07-22 00:00:00 UTC') + $offset;
        [$east, $north] = $path[0];
        $points = [$this->trackPoint($east, $north, $time, $segment)];

        foreach (array_slice($path, 1) as [$nextEast, $nextNorth]) {
            $distance = hypot($nextEast - $east, $nextNorth - $north);
            $steps = max(1, (int) ceil($distance / $sampleMetres));

            for ($step = 1; $step <= $steps; $step++) {
                $time += max(1, (int) round($distance / $steps));
                $fraction = $step / $steps;
                $points[] = $this->trackPoint(
                    $east + ($nextEast - $east) * $fraction,
                    $north + ($nextNorth - $north) * $fraction,
                    $time,
                    $segment,
                );
            }

            $east = $nextEast;
            $north = $nextNorth;
        }

        return $points;
    }

    private function trackPoint(float $east, float $north, int $time, int $segment): array
    {
        return [
            'lat' => 14.49975 + $north / 110660,
            'lng' => 120.99975 + $east / 107800,
            'speed' => 3.6,
            'gpsTime' => gmdate(DATE_ATOM, $time),
            'positionType' => '1',
            'segment' => $segment,
        ];
    }
}
