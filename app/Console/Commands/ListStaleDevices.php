<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class ListStaleDevices extends Command
{
    protected $signature = 'devices:stale {--days=365 : Days offline threshold}';

    protected $description = 'List tractors/devices offline for more than N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Finding devices offline for more than {$days} day(s)...");
        $this->newLine();

        $devices = Device::with(['tractor', 'latestLocation'])
            ->where('is_active', true)
            ->get()
            ->filter(function (Device $device) use ($cutoff): bool {
                $loc = $device->latestLocation;

                if (! $loc) {
                    return true; // never reported
                }

                $lastSeen = $loc->heartbeat_at ?? $loc->created_at;

                return $lastSeen && $lastSeen->lt($cutoff);
            })
            ->sortByDesc(function (Device $device) {
                $loc = $device->latestLocation;

                if (! $loc) {
                    return PHP_INT_MAX;
                }

                $lastSeen = $loc->heartbeat_at ?? $loc->created_at;

                return $lastSeen ? $lastSeen->diffInDays(now()) : 0;
            })
            ->values();

        if ($devices->isEmpty()) {
            $this->info('No stale devices found. All devices have reported within the last '.$days.' day(s).');

            return self::SUCCESS;
        }

        $rows = $devices->map(function (Device $device): array {
            $loc = $device->latestLocation;
            $lastSeen = $loc ? ($loc->heartbeat_at ?? $loc->created_at) : null;

            return [
                'IMEI' => $device->imei,
                'Device' => $device->device_name ?? '—',
                'Tractor' => $device->tractor?->no_plate ?? '—',
                'Last Seen' => $lastSeen?->diffForHumans() ?? 'Never',
                'Days Offline' => $lastSeen ? (string) $lastSeen->diffInDays(now()) : 'N/A',
            ];
        })->all();

        $this->table(
            ['IMEI', 'Device', 'Tractor', 'Last Seen', 'Days Offline'],
            $rows
        );

        $this->newLine();
        $this->info("Total: {$devices->count()} stale device(s) found.");

        return self::SUCCESS;
    }
}
