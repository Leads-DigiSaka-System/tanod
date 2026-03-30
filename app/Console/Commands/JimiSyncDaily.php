<?php

namespace App\Console\Commands;

use App\Services\Jimi\JimiDeviceService;
use Illuminate\Console\Command;

class JimiSyncDaily extends Command
{
    protected $signature = 'jimi:sync-daily';

    protected $description = 'Sync all devices and locations from JIMI (daily full sync)';

    public function handle(JimiDeviceService $service): int
    {
        $this->info('Syncing devices from JIMI...');
        $synced = $service->syncDevicesFromJimi();
        $this->info("  ✅ Synced {$synced} devices.");

        $this->info('Syncing locations from JIMI...');
        $locations = $service->fetchAndStoreLocations(forceRefresh: true);
        $this->info('  ✅ Synced locations for ' . count($locations) . ' devices.');

        $this->newLine();
        $this->info('Daily JIMI sync complete.');

        return self::SUCCESS;
    }
}
