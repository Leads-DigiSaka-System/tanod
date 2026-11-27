<?php

namespace App\Services;

class TractorServiceAreaEstimator
{
    private const CELL_METRES = 100;

    private const WGS84_SEMI_MAJOR_AXIS = 6378137.0;

    private const WGS84_ECCENTRICITY = 0.08181919084262149;

    /** EPSG:6933 easting scale at the 30-degree standard parallel. */
    private const EASTING_METRES_PER_RADIAN = 5528256.639292835;

    private const MAX_STATIONARY_STEP_METRES = 5;

    private const MAX_STATIONARY_DRIFT_METRES = 15;

    private const MAX_MOVING_STEP_METRES = 90;

    /**
     * Count unique equal-area cells with repeated short stops between local passes.
     * Points and parking intervals are in chronological order.
     */
    public function estimate(array $points, array $parkingEvents): array
    {
        $settings = config('jimi.service_area');
        $parking = array_map(fn (array $event): array => [
            'start' => strtotime($event['startTime']),
            'end' => strtotime($event['endTime']),
        ], $parkingEvents);

        $candidates = [];
        $previous = null;
        $movingCell = null;
        $stop = null;
        $parkingIndex = 0;
        $hasObservations = false;

        foreach ($points as $point) {
            $from = $previous;
            $to = $this->projectPoint($point);
            $previous = $to;
            $elapsed = $from === null ? 0 : $to['time'] - $from['time'];

            if ($from === null || $from['segment'] !== $to['segment'] || ! $from['gps'] || ! $to['gps']
                || $elapsed <= 0 || $elapsed > $settings['max_sample_seconds']) {
                $movingCell = null;
                $stop = null;

                continue;
            }

            $hasObservations = true;

            while (isset($parking[$parkingIndex]) && $parking[$parkingIndex]['end'] <= $from['time']) {
                $parkingIndex++;
            }

            $isParked = isset($parking[$parkingIndex]) && $parking[$parkingIndex]['start'] < $to['time'];

            if ($isParked || $from['ignitionOn'] === false || $to['ignitionOn'] === false || $from['cell'] !== $to['cell']) {
                $movingCell = null;
                $stop = null;

                continue;
            }

            $cellId = $to['cell'];
            $dx = $to['easting'] - $from['easting'];
            $dy = $to['northing'] - $from['northing'];
            $distance = hypot($dx, $dy);
            $speed = max($from['speed'], $to['speed']);

            if ($movingCell === $cellId && $distance <= self::MAX_STATIONARY_STEP_METRES && $speed <= 1) {
                $stop ??= $from;

                if ($settings['max_stop_seconds'] < $to['time'] - $stop['time']
                    || hypot($to['easting'] - $stop['easting'], $to['northing'] - $stop['northing']) > self::MAX_STATIONARY_DRIFT_METRES) {
                    $movingCell = null;
                    $stop = null;
                }

                continue;
            }

            $impliedSpeed = $distance / $elapsed * 3.6;

            if ($distance <= self::MAX_STATIONARY_STEP_METRES || $distance > self::MAX_MOVING_STEP_METRES
                || $speed < 1 || max($speed, $impliedSpeed) > $settings['max_speed_kph']) {
                $movingCell = null;
                $stop = null;

                continue;
            }

            $heading = [$dx / $distance, $dy / $distance];
            $cell = $candidates[$cellId] ?? [
                'column' => $to['column'],
                'row' => $to['row'],
                'firstHeading' => $heading,
                'changedHeading' => false,
                'stopCount' => 0,
                'distance' => 0,
                'movingSeconds' => 0,
            ];

            if ($movingCell === $cellId && $stop !== null && $settings['min_stop_seconds'] <= $from['time'] - $stop['time']) {
                $cell['stopCount']++;
            }

            // A heading change helps screen out straight-line road traffic with brief stops.
            $alignment = $cell['firstHeading'][0] * $heading[0] + $cell['firstHeading'][1] * $heading[1];
            $cell['changedHeading'] = $cell['changedHeading'] || $alignment <= M_SQRT1_2;
            $cell['distance'] += $distance;
            $cell['movingSeconds'] += $elapsed;
            $candidates[$cellId] = $cell;
            $movingCell = $cellId;
            $stop = null;
        }

        $cells = [];

        foreach ($candidates as $cell) {
            if ($cell['changedHeading'] && $cell['stopCount'] >= $settings['min_stops_per_cell']
                && $cell['distance'] >= $settings['min_movement_metres'] && $cell['movingSeconds'] >= $settings['min_moving_seconds']) {
                $cells[] = [
                    'bounds' => $this->cellBounds($cell['column'], $cell['row']),
                    'stopCount' => $cell['stopCount'],
                ];
            }
        }

        return ['available' => $hasObservations, 'hectares' => count($cells), 'cells' => $cells];
    }

    private function projectPoint(array $point): array
    {
        $easting = self::EASTING_METRES_PER_RADIAN * deg2rad($point['lng']);
        $northing = $this->northing($point['lat']);
        $column = (int) floor($easting / self::CELL_METRES);
        $row = (int) floor($northing / self::CELL_METRES);

        return [
            'easting' => $easting,
            'northing' => $northing,
            'cell' => $column.':'.$row,
            'column' => $column,
            'row' => $row,
            'time' => strtotime($point['gpsTime']),
            'speed' => $point['speed'],
            'segment' => $point['segment'],
            'gps' => in_array($point['positionType'] ?? null, [null, '1', 'GPS'], true),
            'ignitionOn' => $point['ignitionOn'] ?? null,
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
