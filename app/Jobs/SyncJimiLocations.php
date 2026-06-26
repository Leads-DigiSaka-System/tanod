<?php

namespace App\Jobs;

use App\Services\Jimi\JimiDeviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetches live device locations from Jimi and stores in device_locations table.
 * Run every 5-10 minutes via scheduler.
 */
class SyncJimiLocations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(JimiDeviceService $service): void
    {
        $locations = $service->fetchAndStoreLocations(forceRefresh: true);
        Log::info('SyncJimiLocations: stored locations for '.count($locations).' devices');
    }
}
