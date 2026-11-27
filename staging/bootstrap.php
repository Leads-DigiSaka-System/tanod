<?php

use App\Services\Jimi\JimiAuthService;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Inertia\Inertia;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/StagingJimiAuthService.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->singleton(JimiAuthService::class, StagingJimiAuthService::class);

$fleetMode = getenv('TANOD_STAGING_FLEET_MODE') ?: 'unconfigured';
$app->afterBootstrapping(LoadConfiguration::class, function () use ($fleetMode): void {
    $cachePath = storage_path("framework/cache/data/tanod-{$fleetMode}");
    config([
        'cache.stores.file.path' => $cachePath,
        'cache.stores.file.lock_path' => $cachePath,
    ]);
});

$app->booted(function () use ($app, $fleetMode): void {
    $app->make('db')->connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT',
        fn (string $date): string => substr($date, 0, 7), 2);
    Inertia::share('stagingFleetMode', $fleetMode);
});

return $app;
