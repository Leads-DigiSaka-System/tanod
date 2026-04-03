<?php

namespace App\Console\Commands;

use App\Models\Tractor;
use Illuminate\Console\Command;

class BackfillTractorDetails extends Command
{
    protected $signature = 'tractors:backfill';

    protected $description = 'Backfill missing no_plate and model on tractors from their linked device data';

    public function handle(): int
    {
        $tractors = Tractor::with('device')
            ->where(function ($q) {
                $q->whereNull('no_plate')
                    ->orWhere('no_plate', '')
                    ->orWhereNull('model')
                    ->orWhere('model', '');
            })
            ->get();

        if ($tractors->isEmpty()) {
            $this->info('All tractors already have plate number and model. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Found {$tractors->count()} tractor(s) with missing plate or model.");

        $updated = 0;

        foreach ($tractors as $tractor) {
            $device = $tractor->device;
            $changes = [];

            if (empty($tractor->no_plate) && $device?->device_name) {
                $tractor->no_plate = $device->device_name;
                $changes[] = "no_plate = {$device->device_name}";
            }

            if (empty($tractor->model) && $device?->device_model) {
                $tractor->model = $device->device_model;
                $changes[] = "model = {$device->device_model}";
            }

            if (! empty($changes)) {
                $tractor->save();
                $updated++;
                $this->line("   ✅ Tractor #{$tractor->id} ({$tractor->imei}): ".implode(', ', $changes));
            }
        }

        $this->info("Updated {$updated} tractor(s).");

        return self::SUCCESS;
    }
}
