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
 * Syncs the device list from Jimi into local database.
 * Run this periodically (e.g. every 30 minutes) or on-demand.
 */
class SyncJimiDevices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function handle(JimiDeviceService $service): void
    {
        $count = $service->syncDevicesFromJimi();
        Log::info("SyncJimiDevices: synced {$count} devices");
    }
}
