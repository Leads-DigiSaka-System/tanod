<?php

namespace App\Jobs;

use App\Models\Tractor;
use App\Services\Jimi\JimiTrackingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Updates tractor total_distance and running_hours from Jimi mileage data.
 * Run daily via scheduler.
 */
class UpdateTractorDistances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function handle(JimiTrackingService $tracking): void
    {
        $tractors = Tractor::whereNotNull('imei')
            ->where('is_active', true)
            ->get();

        if ($tractors->isEmpty()) {
            return;
        }

        $imeis = $tractors->pluck('imei')->toArray();
        // Use last 30 days to get reliable odometer readings without hitting
        // the 100-record API limit per batch call
        $endTime = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $beginTime = Carbon::now('UTC')->subDays(30)->format('Y-m-d H:i:s');

        $mileageData = $tracking->fetchBatchMileage($imeis, $beginTime, $endTime);

        $updated = 0;
        foreach ($tractors as $tractor) {
            if (isset($mileageData[$tractor->imei])) {
                $data = $mileageData[$tractor->imei];
                // odometer_km is the cumulative reading — set it absolutely
                $tractor->update([
                    'total_distance' => round($data['odometer_km'], 2),
                    'running_hours' => round($data['runtime_seconds'] / 3600, 2),
                ]);
                $updated++;
            }
        }

        Log::info("UpdateTractorDistances: updated {$updated} tractors");
    }
}
