<?php

require __DIR__.'/fixtures.php';

header('Content-Type: application/json');

$from = strtotime(($_POST['begin_time'] ?? $_POST['start_time'] ?? '').' UTC');
$to = strtotime(($_POST['end_time'] ?? '').' UTC');
$imei = (string) ($_POST['imei'] ?? $_POST['imeis'] ?? '');
$method = $_POST['method'] ?? '';

$payload = match ($method) {
    'jimi.oauth.token.get' => ['code' => 0, 'result' => ['accessToken' => 'local-staging', 'expiresIn' => 7200]],
    'jimi.user.device.location.list' => ['code' => 0, 'result' => stagingLiveLocations()],
    'jimi.device.track.list' => ['code' => 0, 'result' => stagingTrackPoints($imei, $from, $to)],
    'jimi.open.platform.report.parking' => (function () use ($imei, $from, $to): array {
        $rows = stagingParkingReport($imei, $from, $to);

        return ['code' => 0, 'data' => ['dataTotalRows' => count($rows), 'rows' => $rows]];
    })(),
    default => ['code' => 0, 'result' => []],
};

echo json_encode($payload, JSON_THROW_ON_ERROR);
