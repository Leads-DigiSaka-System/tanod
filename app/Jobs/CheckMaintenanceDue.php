<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Checks all active tractors for maintenance due thresholds
 * and creates alerts + notifications. Run daily.
 */
class CheckMaintenanceDue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tractors = Tractor::where('is_active', true)
            ->whereNotNull('maintenance_km')
            ->with('device')
            ->get();

        $count = 0;

        foreach ($tractors as $tractor) {
            if ($tractor->isMaintenanceDue()) {
                // Avoid duplicate alerts for same tractor on same day
                $existsToday = Alert::where('tractor_id', $tractor->id)
                    ->where('type', 'maintenance_due')
                    ->whereDate('created_at', today())
                    ->exists();

                if ($existsToday) continue;

                Alert::create([
                    'device_id' => $tractor->device_id,
                    'tractor_id' => $tractor->id,
                    'type' => 'maintenance_due',
                    'title' => "Maintenance Due: {$tractor->no_plate}",
                    'message' => "Tractor {$tractor->no_plate} ({$tractor->brand} {$tractor->model}) has reached its maintenance km threshold ({$tractor->total_distance} km).",
                    'meta' => [
                        'total_distance' => $tractor->total_distance,
                        'maintenance_km' => $tractor->maintenance_km,
                    ],
                ]);

                // Notify admins
                $admins = User::role(['super-admin', 'sub-admin'])->get();
                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'maintenance',
                        'title' => "Maintenance Due: {$tractor->no_plate}",
                        'body' => "Tractor {$tractor->no_plate} needs maintenance at {$tractor->total_distance} km.",
                        'data' => ['tractor_id' => $tractor->id, 'alert_type' => 'maintenance_due'],
                    ]);
                }

                $count++;
            }
        }

        Log::info("CheckMaintenanceDue: created {$count} alerts");
    }
}
