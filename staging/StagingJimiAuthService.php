<?php

use App\Services\Jimi\JimiAuthService;
use GuzzleHttp\Client;

class StagingJimiAuthService extends JimiAuthService
{
    private const READ_METHODS = [
        'jimi.oauth.token.get',
        'jimi.user.device.location.list',
        'jimi.device.track.list',
        'jimi.open.platform.report.parking',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
            'allow_redirects' => false,
        ]);
    }

    public function makeApiCall(array $params): array
    {
        $fleetMode = getenv('TANOD_STAGING_FLEET_MODE');
        if (! in_array($fleetMode, ['sample', 'tracksolid'], true)) {
            throw new RuntimeException('TrackSolid connection required.');
        }
        if (! in_array($params['method'] ?? null, self::READ_METHODS, true)) {
            throw new RuntimeException('This TrackSolid operation is unavailable in staging.');
        }
        if ($fleetMode === 'tracksolid' && parse_url(config('jimi.base_url'), PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('The live TrackSolid API URL must use HTTPS.');
        }

        return parent::makeApiCall($params);
    }
}
