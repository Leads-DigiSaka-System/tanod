<?php

namespace App\Services;

class TractorServiceAreaEstimator
{
    private const CELL_METRES = 100;

    private const WGS84_SEMI_MAJOR_AXIS = 6378137.0;

    private const WGS84_ECCENTRICITY = 0.08181919084262149;

    /** EPSG:6933 easting scale at the 30-degree standard parallel. */
    private const EASTING_METRES_PER_RADIAN = 5528256.639292835;

    private const MIN_STEP_METRES = 5;

    private const MAX_STEP_METRES = 150;

    /** Limit comparisons to nearby passes in track order. */
    private const NEARBY_PASSES = 12;

    /** Cosine of 30 degrees, used for heading changes and opposing lanes. */
    private const HEADING_ALIGNMENT = 0.8660254037844386;

    /**
     * Count 1 ha cells with repeated slow passes in a continuous GPS track.
     * Points come from the cleaned track in chronological order.
     */
    public function estimate(array $points): array
    {
        $settings = config('jimi.service_area');
        $latitude = deg2rad($points[0]['lat'] ?? 0);
        $eastScale = self::WGS84_SEMI_MAJOR_AXIS * cos($latitude)
            / sqrt(1 - (self::WGS84_ECCENTRICITY * sin($latitude)) ** 2)
            / self::EASTING_METRES_PER_RADIAN;
        $eastScale = max(0.02, $eastScale);

        [$passes, $hasObservations] = $this->movingPasses($points, $settings, $eastScale);
        $workIntervals = $this->workIntervals($passes, $settings);
        $cellDistances = [];

        foreach ($workIntervals as $index => $intervals) {
            $this->addPassDistance($passes[$index], $intervals, $cellDistances, $eastScale);
        }

        $cells = [];

        foreach ($cellDistances as $cell => $distance) {
            if ($distance < $settings['min_cell_distance_metres']) {
                continue;
            }

            [$column, $row] = explode(':', $cell);
            $cells[] = ['bounds' => $this->cellBounds((int) $column, (int) $row)];
        }

        return ['available' => $hasObservations, 'hectares' => count($cells), 'cells' => $cells];
    }

    private function movingPasses(array $points, array $settings, float $eastScale): array
    {
        $passes = [];
        $path = [];
        $run = 0;
        $heading = null;
        $previous = null;
        $anchor = null;
        $hasObservations = false;

        foreach ($points as $point) {
            $current = $this->projectPoint($point, $eastScale);
            $elapsed = $previous === null ? 0 : $current['time'] - $previous['time'];
            $usablePair = $previous !== null && $previous['gps'] && $current['gps']
                && $previous['segment'] === $current['segment']
                && $elapsed > 0 && $elapsed <= $settings['max_sample_seconds'];
            $hasObservations = $hasObservations || $usablePair;
            $previousSpeed = $previous['speed'] ?? 0;
            $previous = $current;
            $anchor ??= $current;

            $step = $this->vector($anchor, $current);
            $distance = hypot(...$step);
            $stepSeconds = $current['time'] - $anchor['time'];
            $speed = $stepSeconds > 0 ? $distance / $stepSeconds * 3.6 : 0;
            $moving = $distance >= self::MIN_STEP_METRES;

            if (! $usablePair || $stepSeconds > $settings['max_sample_seconds'] || $distance > self::MAX_STEP_METRES
                || max($previousSpeed, $current['speed']) > $settings['max_speed_kph']
                || ($moving && ($speed < $settings['min_speed_kph'] || $speed > $settings['max_speed_kph']))) {
                $this->finishPass($passes, $path, $run, $settings['min_overlap_metres']);
                $path = [];
                $heading = null;
                $anchor = $current;
                $run++;

                continue;
            }

            if (! $moving) {
                continue;
            }

            $direction = [$step[0] / $distance, $step[1] / $distance];
            if ($heading !== null && $this->dot($heading, $direction) < self::HEADING_ALIGNMENT) {
                $this->finishPass($passes, $path, $run, $settings['min_overlap_metres']);
                $path = [];
                $heading = null;
            }

            if ($heading === null) {
                $heading = $direction;
                $path[] = $anchor;
            }

            $path[] = $current;
            $anchor = $current;
        }

        $this->finishPass($passes, $path, $run, $settings['min_overlap_metres']);

        return [$passes, $hasObservations];
    }

    private function finishPass(array &$passes, array $path, int $run, float $minimumLength): void
    {
        if (count($path) < 2) {
            return;
        }

        $start = $path[0];
        $end = end($path);
        $vector = $this->vector($start, $end);
        $length = hypot(...$vector);

        if ($length >= $minimumLength) {
            $heading = [$vector[0] / $length, $vector[1] / $length];
            $passes[] = compact('start', 'end', 'path', 'run', 'length', 'heading');
        }
    }

    private function workIntervals(array $passes, array $settings): array
    {
        $intervals = [];
        $lastIndex = count($passes) - 1;

        for ($index = 1; $index < $lastIndex; $index++) {
            $middle = $passes[$index];
            $before = $after = [];

            for ($neighbor = max(0, $index - self::NEARBY_PASSES); $neighbor <= min($lastIndex, $index + self::NEARBY_PASSES); $neighbor++) {
                $pass = $passes[$neighbor];
                $gap = max($middle['start']['time'] - $pass['end']['time'], $pass['start']['time'] - $middle['end']['time']);

                if ($pass['run'] !== $middle['run'] || $gap > $settings['max_pass_gap_seconds']
                    || $this->dot($pass['heading'], $middle['heading']) > -self::HEADING_ALIGNMENT) {
                    continue;
                }

                foreach ($this->overlappingLanes($middle, $pass, $settings) as $lane) {
                    $lane['index'] = $neighbor;
                    if ($neighbor < $index) {
                        $before[] = $lane;
                    } else {
                        $after[] = $lane;
                    }
                }
            }

            // Three distinct lanes prevent a single U-turn or road retrace from counting.
            foreach ($before as $first) {
                foreach ($after as $last) {
                    $start = max($first['start'], $last['start']);
                    $end = min($first['end'], $last['end']);

                    if ($first['side'] === $last['side'] || $end - $start < $settings['min_overlap_metres']) {
                        continue;
                    }

                    $intervals[$index][] = [$start / $middle['length'], $end / $middle['length']];

                    foreach ([$first, $last] as $lane) {
                        $span = $lane['alongStart'] - $lane['alongEnd'];
                        $intervals[$lane['index']][] = [($lane['alongStart'] - $end) / $span, ($lane['alongStart'] - $start) / $span];
                    }
                }
            }
        }

        return $intervals;
    }

    /**
     * Find the part of a reciprocal pass that stays within the lane-spacing limits.
     * Distances are measured along and across the middle pass's axis.
     */
    private function overlappingLanes(array $middle, array $pass, array $settings): array
    {
        $axis = $middle['heading'];
        $normal = [-$axis[1], $axis[0]];
        $start = $this->vector($middle['start'], $pass['start']);
        $end = $this->vector($middle['start'], $pass['end']);
        $alongStart = $this->dot($start, $axis);
        $alongEnd = $this->dot($end, $axis);
        $from = max(0, $alongEnd);
        $to = min($middle['length'], $alongStart);

        if ($to - $from < $settings['min_overlap_metres']) {
            return [];
        }

        $offset = $this->dot($start, $normal);
        $slope = ($this->dot($end, $normal) - $offset) / ($alongEnd - $alongStart);
        $lanes = [];

        foreach ([-1, 1] as $side) {
            $lower = $settings['min_lane_spacing_metres'];
            $upper = $settings['max_lane_spacing_metres'];
            $start = $from;
            $end = $to;
            $signedOffset = $side * $offset;
            $signedSlope = $side * $slope;

            if (abs($signedSlope) < 1e-9) {
                if ($signedOffset < $lower || $signedOffset > $upper) {
                    continue;
                }
            } else {
                $atLower = $alongStart + ($lower - $signedOffset) / $signedSlope;
                $atUpper = $alongStart + ($upper - $signedOffset) / $signedSlope;
                $start = max($start, min($atLower, $atUpper));
                $end = min($end, max($atLower, $atUpper));
            }

            if ($end - $start >= $settings['min_overlap_metres']) {
                $lanes[] = compact('start', 'end', 'side', 'alongStart', 'alongEnd');
            }
        }

        return $lanes;
    }

    private function addPassDistance(array $pass, array $intervals, array &$cellDistances, float $eastScale): void
    {
        // A single pass can match several neighbours. Merge its intervals before counting.
        sort($intervals);
        $merged = [];

        foreach ($intervals as [$start, $end]) {
            $last = array_key_last($merged);
            if ($last !== null && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($end, $merged[$last][1]);
            } else {
                $merged[] = [$start, $end];
            }
        }

        $from = $pass['start'];
        $previousProgress = 0.0;

        foreach (array_slice($pass['path'], 1) as $to) {
            $progress = $this->dot($this->vector($pass['start'], $to), $pass['heading']) / $pass['length'];
            $span = $progress - $previousProgress;

            foreach ($merged as [$start, $end]) {
                $coveredFrom = max($start, $previousProgress);
                $coveredTo = min($end, $progress);
                if ($coveredTo > $coveredFrom) {
                    $this->addStepDistance($from, $to, ($coveredFrom - $previousProgress) / $span,
                        ($coveredTo - $previousProgress) / $span, $cellDistances, $eastScale);
                }
            }

            $from = $to;
            $previousProgress = $progress;
        }
    }

    /** Split observed movement at grid boundaries and count only the distance inside each cell. */
    private function addStepDistance(array $from, array $to, float $start, float $end, array &$cellDistances, float $eastScale): void
    {
        [$dx, $dy] = $this->vector($from, $to);
        $width = self::CELL_METRES * $eastScale;
        $height = self::CELL_METRES / $eastScale;
        $cuts = [$start, $end];

        foreach ([[$from['x'], $dx, $width], [$from['y'], $dy, $height]] as [$origin, $step, $size]) {
            $low = $origin + min($start * $step, $end * $step);
            $high = $origin + max($start * $step, $end * $step);

            for ($boundary = (floor($low / $size) + 1) * $size; $boundary < $high; $boundary += $size) {
                $cuts[] = ($boundary - $origin) / $step;
            }
        }

        sort($cuts);
        $length = hypot($dx, $dy);

        for ($index = 1; $index < count($cuts); $index++) {
            $middle = ($cuts[$index - 1] + $cuts[$index]) / 2;
            $column = (int) floor(($from['x'] + $dx * $middle) / $width);
            $row = (int) floor(($from['y'] + $dy * $middle) / $height);
            $cell = $column.':'.$row;
            $cellDistances[$cell] = ($cellDistances[$cell] ?? 0) + $length * ($cuts[$index] - $cuts[$index - 1]);
        }
    }

    private function vector(array $from, array $to): array
    {
        return [$to['x'] - $from['x'], $to['y'] - $from['y']];
    }

    private function dot(array $first, array $second): float
    {
        return $first[0] * $second[0] + $first[1] * $second[1];
    }

    private function projectPoint(array $point, float $eastScale): array
    {
        return [
            'x' => self::EASTING_METRES_PER_RADIAN * deg2rad($point['lng']) * $eastScale,
            'y' => $this->northing($point['lat']) / $eastScale,
            'time' => strtotime($point['gpsTime']),
            'speed' => $point['speed'],
            'segment' => $point['segment'],
            'gps' => in_array($point['positionType'] ?? null, [null, '1', 'GPS'], true),
        ];
    }

    private function northing(float $latitude): float
    {
        $sine = sin(deg2rad($latitude));
        $eccentricitySine = self::WGS84_ECCENTRICITY * $sine;
        $authalicLatitudeTerm = (1 - self::WGS84_ECCENTRICITY ** 2)
            * ($sine / (1 - $eccentricitySine ** 2) + atanh($eccentricitySine) / self::WGS84_ECCENTRICITY);

        return self::WGS84_SEMI_MAJOR_AXIS ** 2 * $authalicLatitudeTerm / (2 * self::EASTING_METRES_PER_RADIAN);
    }

    private function latitude(float $northing): float
    {
        $south = -90.0;
        $north = 90.0;

        for ($iteration = 0; $iteration < 36; $iteration++) {
            $middle = ($south + $north) / 2;
            if ($this->northing($middle) < $northing) {
                $south = $middle;
            } else {
                $north = $middle;
            }
        }

        return ($south + $north) / 2;
    }

    private function cellBounds(int $column, int $row): array
    {
        return [
            'south' => $this->latitude($row * self::CELL_METRES),
            'north' => $this->latitude(($row + 1) * self::CELL_METRES),
            'west' => rad2deg($column * self::CELL_METRES / self::EASTING_METRES_PER_RADIAN),
            'east' => rad2deg(($column + 1) * self::CELL_METRES / self::EASTING_METRES_PER_RADIAN),
        ];
    }
}
