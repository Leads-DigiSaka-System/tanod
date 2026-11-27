<?php

namespace App\Services;

use Carbon\Carbon;

class TractorTrackFormatter
{
    private const MOVING_SPEED_THRESHOLD = 3.0;

    private const PARKING_MATCH_SECONDS = 120;

    private const PARKING_MATCH_KM = 0.1;

    /**
     * Format raw JIMI track points into structured data.
     */
    public function format(array $points, array $parkingEvents = []): array
    {
        if (empty($points)) {
            return $this->emptyTrackSummary();
        }

        $rawPointCount = count($points);
        $normalized = [];
        $invalidPointCount = 0;

        foreach ($points as $point) {
            $lat = filter_var($point['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($point['lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $time = $this->parseTrackTimestamp($point['gpsTime'] ?? $point['positionTime'] ?? null);

            if ($lat === false || $lng === false || ! $this->isValidTrackCoordinate($lat, $lng) || $time === null) {
                $invalidPointCount++;

                continue;
            }

            $speed = max((float) ($point['speed'] ?? $point['gpsSpeed'] ?? 0), 0);
            $direction = fmod((float) ($point['course'] ?? $point['direction'] ?? 0), 360);
            $ignition = $point['ignition'] ?? $point['accStatus'] ?? null;

            $normalized[] = [
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $speed,
                'direction' => $direction < 0 ? $direction + 360 : $direction,
                'positionType' => isset($point['posType']) ? strtoupper((string) $point['posType']) : null,
                'ignitionOn' => filled($ignition) ? filter_var($ignition, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
                'gpsTime' => $time->toIso8601String(),
                '_timestamp' => $time->getTimestamp(),
            ];
        }

        usort($normalized, fn (array $left, array $right): int => $left['_timestamp'] <=> $right['_timestamp']);

        $formatted = [];
        $speeds = [];
        $maxSpeed = 0;
        $totalDist = 0;
        $duplicatePointCount = 0;
        $outlierPointCount = 0;
        $segment = 0;
        $gaps = [];
        $seen = [];
        $previous = null;
        $movingDuration = 0;
        $idleDuration = 0;
        $idleRunDuration = 0;
        $stopCount = 0;
        $gapSeconds = max((int) config('jimi.track_gap_minutes', 10), 1) * 60;
        $maxPlausibleSpeed = max((float) config('jimi.track_max_plausible_speed_kph', 120), 1);
        $stopSeconds = max((int) config('jimi.track_stop_minutes', 5), 1) * 60;

        foreach ($normalized as $point) {
            $dedupKey = $point['gpsTime'].'|'.round($point['lat'], 6).'|'.round($point['lng'], 6);

            if (isset($seen[$dedupKey])) {
                $duplicatePointCount++;

                continue;
            }

            $seen[$dedupKey] = true;

            if ($previous !== null) {
                $elapsedSeconds = $point['_timestamp'] - $previous['_timestamp'];
                $distance = $this->haversineDistance(
                    $previous['lat'],
                    $previous['lng'],
                    $point['lat'],
                    $point['lng'],
                );
                $impliedSpeed = $elapsedSeconds > 0 ? $distance / ($elapsedSeconds / 3600) : INF;
                $gapReason = null;

                if ($elapsedSeconds > $gapSeconds) {
                    $gapReason = 'time_gap';
                } elseif ($elapsedSeconds <= 0 || $impliedSpeed > $maxPlausibleSpeed) {
                    $outlierPointCount++;
                    $gaps[] = [
                        'reason' => 'implausible_jump',
                        'fromTime' => $previous['gpsTime'],
                        'toTime' => $point['gpsTime'],
                        'toLat' => $point['lat'],
                        'toLng' => $point['lng'],
                        'markerLat' => $previous['lat'],
                        'markerLng' => $previous['lng'],
                        'duration' => max($elapsedSeconds, 0),
                        'distance' => round($distance, 2),
                    ];

                    continue;
                }

                if ($gapReason !== null) {
                    if ($idleRunDuration >= $stopSeconds) {
                        $stopCount++;
                    }
                    $idleRunDuration = 0;
                    $segment++;

                    if (! $this->isConfirmedParkingGap($previous, $point, $parkingEvents)) {
                        $gaps[] = [
                            'reason' => $gapReason,
                            'fromTime' => $previous['gpsTime'],
                            'toTime' => $point['gpsTime'],
                            'toLat' => $point['lat'],
                            'toLng' => $point['lng'],
                            'markerLat' => $previous['lat'],
                            'markerLng' => $previous['lng'],
                            'duration' => $elapsedSeconds,
                            'distance' => round($distance, 2),
                        ];
                    }
                } else {
                    $totalDist += $distance;

                    if (max($previous['speed'], $point['speed']) >= self::MOVING_SPEED_THRESHOLD) {
                        if ($idleRunDuration >= $stopSeconds) {
                            $stopCount++;
                        }
                        $idleRunDuration = 0;
                        $movingDuration += $elapsedSeconds;
                    } else {
                        $idleDuration += $elapsedSeconds;
                        $idleRunDuration += $elapsedSeconds;
                    }
                }
            }

            $point['segment'] = $segment;
            unset($point['_timestamp']);
            $formatted[] = $point;
            $speeds[] = $point['speed'];
            if ($point['speed'] > $maxSpeed) {
                $maxSpeed = $point['speed'];
            }

            $previous = [...$point, '_timestamp' => Carbon::parse($point['gpsTime'])->getTimestamp()];
        }

        if ($idleRunDuration >= $stopSeconds) {
            $stopCount++;
        }

        if ($formatted === []) {
            return [
                ...$this->emptyTrackSummary(),
                'rawPointCount' => $rawPointCount,
                'invalidPointCount' => $invalidPointCount,
            ];
        }

        $avgSpeed = count($speeds) > 0 ? array_sum($speeds) / count($speeds) : 0;
        $startTime = $formatted[0]['gpsTime'] ?? null;
        $endTime = end($formatted)['gpsTime'] ?? null;

        $duration = 0;
        if ($startTime && $endTime) {
            $duration = Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime));
        }

        return [
            'points' => $formatted,
            'totalPoints' => count($formatted),
            'distance' => round($totalDist, 2),     // km
            'maxSpeed' => round($maxSpeed, 1),       // km/h
            'avgSpeed' => round($avgSpeed, 1),       // km/h
            'duration' => $duration,                  // seconds
            'startTime' => $startTime,
            'endTime' => $endTime,
            'rawPointCount' => $rawPointCount,
            'invalidPointCount' => $invalidPointCount,
            'duplicatePointCount' => $duplicatePointCount,
            'outlierPointCount' => $outlierPointCount,
            'gapCount' => count($gaps),
            'segmentCount' => $segment + 1,
            'gaps' => $gaps,
            'movingDuration' => $movingDuration,
            'idleDuration' => $idleDuration,
            'stopCount' => $stopCount,
        ];
    }

    public function formatParkingEvents(array $rows, string $imei, string $beginTime, string $endTime): array
    {
        $rangeStart = Carbon::parse($beginTime, 'UTC');
        $rangeEnd = Carbon::parse($endTime, 'UTC');
        $events = [];

        foreach ($rows as $row) {
            if ((string) ($row['imei'] ?? '') !== $imei) {
                continue;
            }

            $start = $this->parseTrackTimestamp($row['startTime'] ?? null);
            $end = $this->parseTrackTimestamp($row['endTime'] ?? null);
            $lat = filter_var($row['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($row['lng'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($start === null || $end === null || $lat === false || $lng === false
                || ! $this->isValidTrackCoordinate($lat, $lng)) {
                continue;
            }

            $start = $start->max($rangeStart);
            $end = $end->min($rangeEnd);
            $duration = $end->getTimestamp() - $start->getTimestamp();

            if ($duration > 0) {
                $events[$start->getTimestamp().'|'.$end->getTimestamp()] = [
                    'lat' => $lat,
                    'lng' => $lng,
                    'startTime' => $start->toIso8601String(),
                    'endTime' => $end->toIso8601String(),
                    'duration' => $duration,
                ];
            }
        }

        usort($events, fn (array $left, array $right): int => $left['startTime'] <=> $right['startTime']);

        return $events;
    }

    private function isConfirmedParkingGap(array $previous, array $point, array $events): bool
    {
        foreach ($events as $event) {
            $start = strtotime($event['startTime']);
            $end = strtotime($event['endTime']);

            if ($start > $previous['_timestamp'] + self::PARKING_MATCH_SECONDS) {
                break;
            }

            if ($start >= $point['_timestamp'] || $end <= $previous['_timestamp']
                || $end < $point['_timestamp'] - self::PARKING_MATCH_SECONDS) {
                continue;
            }

            $distanceBeforeParking = $this->haversineDistance($previous['lat'], $previous['lng'], $event['lat'], $event['lng']);
            $distanceAfterParking = $this->haversineDistance($point['lat'], $point['lng'], $event['lat'], $event['lng']);

            if (min($distanceBeforeParking, $distanceAfterParking) <= self::PARKING_MATCH_KM) {
                return true;
            }
        }

        return false;
    }

    public function parkedDuration(array $events): int
    {
        $seconds = 0;
        $coveredUntil = 0;

        foreach ($events as $event) {
            $start = strtotime($event['startTime']);
            $end = strtotime($event['endTime']);
            $seconds += max(0, $end - max($start, $coveredUntil));
            $coveredUntil = max($coveredUntil, $end);
        }

        return $seconds;
    }

    private function emptyTrackSummary(): array
    {
        return [
            'points' => [],
            'totalPoints' => 0,
            'distance' => 0,
            'maxSpeed' => 0,
            'avgSpeed' => 0,
            'duration' => 0,
            'startTime' => null,
            'endTime' => null,
            'rawPointCount' => 0,
            'invalidPointCount' => 0,
            'duplicatePointCount' => 0,
            'outlierPointCount' => 0,
            'gapCount' => 0,
            'segmentCount' => 0,
            'gaps' => [],
            'movingDuration' => 0,
            'idleDuration' => 0,
            'stopCount' => 0,
        ];
    }

    private function parseTrackTimestamp(mixed $timestamp): ?Carbon
    {
        if (blank($timestamp)) {
            return null;
        }

        try {
            return Carbon::parse((string) $timestamp, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isValidTrackCoordinate(float $lat, float $lng): bool
    {
        return is_finite($lat)
            && is_finite($lng)
            && $lat >= -90
            && $lat <= 90
            && $lng >= -180
            && $lng <= 180
            && ! ($lat === 0.0 && $lng === 0.0);
    }

    /**
     * Haversine distance between two GPS coordinates in kilometers.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}
