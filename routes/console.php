<?php

use App\Jobs\CheckMaintenanceDue;
use App\Jobs\SyncJimiAlarms;
use App\Jobs\SyncJimiDevices;
use App\Jobs\SyncJimiLocations;
use App\Jobs\UpdateTractorDistances;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
*/

// Sync device list from Jimi every 30 minutes
Schedule::job(new SyncJimiDevices)->everyThirtyMinutes();

// Record GPS locations for historical track data every 20 minutes
Schedule::job(new SyncJimiLocations)->cron('*/20 * * * *');

// Sync Jimi alarms (geofence, speed, offline, idle) every 2 minutes
Schedule::job(new SyncJimiAlarms)->everyTwoMinutes();

// Update tractor distances daily at 2 AM
Schedule::job(new UpdateTractorDistances)->dailyAt('02:00');

// Check maintenance due daily at 6 AM
Schedule::job(new CheckMaintenanceDue)->dailyAt('06:00');

// Full JIMI sync (devices + locations) every 20 minutes
Schedule::command('jimi:sync-daily')->cron('*/20 * * * *')->withoutOverlapping();

// Warm the Jimi total machine hours cache every hour
Schedule::call(function () {
    app(\App\Services\Jimi\JimiTrackingService::class)->getTotalMachineHours(forceRefresh: true);
})->hourly()->name('warm-jimi-machine-hours')->withoutOverlapping();

// Process expired account deletion requests daily at 3 AM
Schedule::command('accounts:process-deletions')->dailyAt('03:00');
