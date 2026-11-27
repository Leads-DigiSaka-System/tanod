<?php

const STAGING_TRACTORS = [
    '990000000000001' => ['name' => 'Demo Field Tractor', 'lat' => 14.49975, 'lng' => 120.99975, 'status' => 1, 'acc' => 1, 'speed' => 4],
    '990000000000002' => ['name' => 'Demo Road Tractor', 'lat' => 14.507, 'lng' => 121.004, 'status' => 0, 'acc' => 0, 'speed' => 0],
    '990000000000003' => ['name' => 'Demo Parked Tractor', 'lat' => 14.5005, 'lng' => 121.001, 'status' => 1, 'acc' => 0, 'speed' => 0],
];

function stagingTimestamp(int $timestamp): string
{
    return gmdate('Y-m-d H:i:s', $timestamp);
}

function stagingTrackStart(): int
{
    return (new DateTimeImmutable('yesterday 08:00', new DateTimeZone('Asia/Manila')))->getTimestamp();
}

function stagingLiveLocations(): array
{
    $locations = [];
    $now = time();

    foreach (STAGING_TRACTORS as $imei => $tractor) {
        $lastSeen = stagingTimestamp($now - ($tractor['status'] ? 10 : 2 * 86400));
        $locations[] = [
            'imei' => (string) $imei,
            'deviceName' => $tractor['name'],
            'lat' => $tractor['lat'],
            'lng' => $tractor['lng'],
            'status' => (string) $tractor['status'],
            'accStatus' => (string) $tractor['acc'],
            'speed' => $tractor['speed'],
            'hbTime' => $lastSeen,
            'gpsTime' => $lastSeen,
            'posType' => 'GPS',
            'gpsNum' => 12,
        ];
    }

    return $locations;
}

function stagingTrackPoints(string $imei, int $from, int $to): array
{
    $start = stagingTrackStart();
    $track = match ($imei) {
        '990000000000001' => [
            [0, 14.4995, 120.99975, 4],
            [20, 14.49975, 120.99975, 0],
            [5420, 14.49975, 120.99975, 3],
            [5440, 14.49975, 121.00003, 0],
            [5460, 14.49975, 121.00003, 0],
            [5480, 14.4999, 121.00003, 3],
            [5500, 14.50017, 121.00003, 0],
            [5520, 14.50017, 121.00003, 0],
            [5540, 14.50017, 120.99988, 3],
            [5560, 14.50017, 120.99975, 3],
            [6720, 14.5045, 121.0015, 11],
            [6760, 14.5052, 121.0020, 12],
        ],
        '990000000000002' => [
            [0, 14.5055, 121.004, 22],
            [20, 14.5065, 121.004, 22],
            [40, 14.507, 121.004, 19],
        ],
        '990000000000003' => [
            [0, 14.5005, 121.001, 0],
            [5400, 14.5005, 121.001, 0],
        ],
        default => [],
    };

    $points = [];
    foreach ($track as [$seconds, $lat, $lng, $speed]) {
        $time = $start + $seconds;
        if ($time >= $from && $time <= $to) {
            $points[] = [
                'lat' => $lat,
                'lng' => $lng,
                'gpsTime' => stagingTimestamp($time),
                'gpsSpeed' => $speed,
                'posType' => 1,
                'ignition' => $imei === '990000000000003' ? 'OFF' : 'ON',
            ];
        }
    }

    return $points;
}

function stagingParkingReport(string $imei, int $from, int $to): array
{
    $start = stagingTrackStart();
    $tractor = STAGING_TRACTORS[$imei] ?? null;
    if ($tractor === null || $imei === '990000000000002') {
        return [];
    }

    $parkedAt = $start + ($imei === '990000000000001' ? 20 : 0);
    $departedAt = $parkedAt + 5400;
    if ($parkedAt >= $to || $departedAt <= $from) {
        return [];
    }

    return [[
        'imei' => $imei,
        'lat' => $tractor['lat'],
        'lng' => $tractor['lng'],
        'startTime' => gmdate(DATE_ATOM, $parkedAt),
        'endTime' => gmdate(DATE_ATOM, $departedAt),
        'durSecond' => '5400',
    ]];
}
