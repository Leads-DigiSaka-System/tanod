<?php

namespace App\Jobs;

use App\Events\AlertCreated;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Notification;
use App\Models\User;
use App\Services\Jimi\JimiGeoFenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Syncs alerts from multiple sources and stores all in the database.
 * 1) Jimi alarm API — geofence breaches for devices with geofences.
 * 2) Local location data — speed alerts for devices exceeding tractor max_speed.
 * 3) Live location data — offline detection (no heartbeat for 30+ min).
 * 4) Live location data — idle detection (online, speed=0 for 60+ min).
 *
 * Notifications are sent to admins + TPS/FCA users assigned to the tractor.
 * FCM push is sent to users with an fcm_token.
 *
 * Runs every 2 minutes.
 */
class SyncJimiAlarms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    private const ALARM_TYPES = [
        1006 => 'geofence_breach',   // enter
        1007 => 'geofence_breach',   // exit
        1019 => 'speed',             // over-speed
    ];

    private const SPEED_THRESHOLD_KPH = 60;

    private const OFFLINE_MINUTES = 30;

    private const IDLE_MINUTES = 60;

    public function handle(JimiGeoFenceService $jimiGeoFence): void
    {
        $totalCreated = 0;

        $totalCreated += $this->syncJimiAlarms($jimiGeoFence);
        $totalCreated += $this->detectSpeedAlerts();
        $totalCreated += $this->detectOfflineAlerts();
        $totalCreated += $this->detectIdleAlerts();

        Log::info("SyncJimiAlarms: created {$totalCreated} alerts total");
    }

    /**
     * Poll Jimi alarm API only for devices that have geofences assigned.
     */
    private function syncJimiAlarms(JimiGeoFenceService $jimiGeoFence): int
    {
        $devices = Device::where('is_active', true)
            ->whereNotNull('imei')
            ->whereHas('geoFences')
            ->with('tractor')
            ->get();

        if ($devices->isEmpty()) {
            return 0;
        }

        $cacheKey = 'jimi_alarm_sync_last_at';
        $lastSync = Cache::get($cacheKey, now()->subMinutes(5)->format('Y-m-d H:i:s'));
        $now = now()->format('Y-m-d H:i:s');

        $created = 0;

        foreach ($devices as $device) {
            try {
                $alarms = $jimiGeoFence->getAlarmList($device->imei, $lastSync, $now);

                foreach ($alarms as $alarm) {
                    $alarmCode = (int) ($alarm['alarmType'] ?? $alarm['alarm_type'] ?? 0);
                    $type = self::ALARM_TYPES[$alarmCode] ?? null;

                    if (! $type) {
                        continue;
                    }

                    $alarmTime = $alarm['alarmTime'] ?? $alarm['alarm_time'] ?? $now;

                    if (Alert::where('device_id', $device->id)->where('type', $type)->where('meta->alarm_time', $alarmTime)->exists()) {
                        continue;
                    }

                    $label = $this->deviceLabel($device);
                    $isEnter = $alarmCode === 1006;

                    $title = match ($type) {
                        'geofence_breach' => ($isEnter ? 'Geofence Enter' : 'Geofence Exit') . ": {$label}",
                        'speed' => "Over-speed: {$label}",
                        default => "Alarm: {$label}",
                    };

                    $message = match ($type) {
                        'geofence_breach' => "{$label} " . ($isEnter ? 'entered' : 'exited') . " a geofence at {$alarmTime}.",
                        'speed' => "{$label} exceeded speed limit at {$alarmTime}.",
                        default => "Alarm {$alarmCode} for {$label} at {$alarmTime}.",
                    };

                    $this->createAlertWithNotifications($device, $type, $title, $message, [
                        'alarm_code' => $alarmCode,
                        'alarm_time' => $alarmTime,
                        'alarm_name' => $alarm['alarmName'] ?? $alarm['alarm_name'] ?? null,
                        'lat' => $alarm['lat'] ?? null,
                        'lng' => $alarm['lng'] ?? null,
                        'imei' => $device->imei,
                    ]);

                    $created++;
                }
            } catch (\Exception $e) {
                Log::warning("SyncJimiAlarms: IMEI {$device->imei}: {$e->getMessage()}");
            }

            usleep(100_000);
        }

        Cache::put($cacheKey, $now, now()->addHours(2));

        return $created;
    }

    /**
     * Detect speed alerts from local device_locations data.
     */
    private function detectSpeedAlerts(): int
    {
        $cacheKey = 'speed_alert_check_last_at';
        $lastCheck = Cache::get($cacheKey, now()->subMinutes(5));
        $now = now();

        $locations = DeviceLocation::where('heartbeat_at', '>=', $lastCheck)
            ->where('speed', '>', 0)
            ->with(['device.tractor'])
            ->get();

        $created = 0;

        foreach ($locations as $loc) {
            $device = $loc->device;
            if (! $device) {
                continue;
            }

            $threshold = $device->tractor?->max_speed ?: self::SPEED_THRESHOLD_KPH;

            if ($loc->speed < $threshold) {
                continue;
            }

            if (Alert::where('device_id', $device->id)->where('type', 'speed')->where('created_at', '>=', now()->subHour())->exists()) {
                continue;
            }

            $label = $this->deviceLabel($device);
            $title = "Over-speed: {$label}";
            $message = "{$label} reached {$loc->speed} km/h (limit: {$threshold} km/h).";

            $this->createAlertWithNotifications($device, 'speed', $title, $message, [
                'speed' => $loc->speed,
                'threshold' => $threshold,
                'lat' => $loc->lat,
                'lng' => $loc->lng,
                'heartbeat_at' => $loc->heartbeat_at?->toDateTimeString(),
                'imei' => $device->imei,
            ]);

            $created++;
        }

        Cache::put($cacheKey, $now, now()->addHours(2));

        return $created;
    }

    /**
     * Detect devices that have been offline for 30+ minutes.
     * One alert per device per 6 hours.
     */
    private function detectOfflineAlerts(): int
    {
        $devices = Device::where('is_active', true)
            ->whereHas('tractor')
            ->with(['tractor', 'latestLocation'])
            ->get();

        $created = 0;
        $threshold = now()->subMinutes(self::OFFLINE_MINUTES);

        foreach ($devices as $device) {
            $lastLoc = $device->latestLocation;

            $isOffline = ! $lastLoc
                || ! $lastLoc->heartbeat_at
                || $lastLoc->heartbeat_at->lt($threshold);

            if (! $isOffline) {
                continue;
            }

            if (Alert::where('device_id', $device->id)->where('type', 'offline')->where('created_at', '>=', now()->subHours(6))->exists()) {
                continue;
            }

            $label = $this->deviceLabel($device);
            $lastSeen = $lastLoc?->heartbeat_at?->diffForHumans() ?? 'never';
            $title = "Device Offline: {$label}";
            $message = "{$label} has been offline since {$lastSeen}.";

            $this->createAlertWithNotifications($device, 'offline', $title, $message, [
                'last_heartbeat' => $lastLoc?->heartbeat_at?->toDateTimeString(),
                'lat' => $lastLoc?->lat,
                'lng' => $lastLoc?->lng,
                'imei' => $device->imei,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Detect tractors that are online but idle (speed=0) for 60+ minutes.
     * One alert per device per 4 hours.
     */
    private function detectIdleAlerts(): int
    {
        $devices = Device::where('is_active', true)
            ->whereHas('tractor')
            ->with(['tractor', 'latestLocation'])
            ->get();

        $created = 0;
        $idleThreshold = now()->subMinutes(self::IDLE_MINUTES);

        foreach ($devices as $device) {
            $lastLoc = $device->latestLocation;

            if (! $lastLoc || ! $lastLoc->heartbeat_at) {
                continue;
            }

            $isOnline = $lastLoc->status === 1;
            $isIdle = $lastLoc->speed == 0;

            if (! $isOnline || ! $isIdle) {
                continue;
            }

            // Check recent locations — all must have speed=0 during idle period
            $recentMovement = DeviceLocation::where('device_id', $device->id)
                ->where('heartbeat_at', '>=', $idleThreshold)
                ->where('speed', '>', 0)
                ->exists();

            if ($recentMovement) {
                continue;
            }

            if (Alert::where('device_id', $device->id)->where('type', 'idle')->where('created_at', '>=', now()->subHours(4))->exists()) {
                continue;
            }

            $label = $this->deviceLabel($device);
            $title = "Idle Alert: {$label}";
            $message = "{$label} has been idle for over " . self::IDLE_MINUTES . ' minutes.';

            $this->createAlertWithNotifications($device, 'idle', $title, $message, [
                'idle_since' => $idleThreshold->toDateTimeString(),
                'lat' => $lastLoc->lat,
                'lng' => $lastLoc->lng,
                'imei' => $device->imei,
            ]);

            $created++;
        }

        return $created;
    }

    private function deviceLabel(Device $device): string
    {
        if ($device->tractor) {
            return $device->tractor->no_plate ?: "Tractor #{$device->tractor->id}";
        }

        return $device->device_name ?: "Device #{$device->id}";
    }

    /**
     * Create alert in DB and send notifications to:
     * - All super-admin and sub-admin users
     * - TPS users in the tractor's groups
     * - FCA users who have the tractor distributed to them
     *
     * Also sends FCM push to all notified users with a token.
     */
    private function createAlertWithNotifications(Device $device, string $type, string $title, string $message, array $meta): void
    {
        $alert = Alert::create([
            'device_id' => $device->id,
            'tractor_id' => $device->tractor?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'meta' => $meta,
        ]);

        $recipients = $this->getAlertRecipients($device);

        foreach ($recipients as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'alert',
                'title' => $title,
                'body' => $message,
                'data' => [
                    'alert_id' => $alert->id,
                    'device_id' => $device->id,
                    'tractor_id' => $device->tractor?->id,
                    'alert_type' => $type,
                ],
            ]);
        }

        $this->sendFcmPush($recipients, $title, $message, [
            'alert_id' => (string) $alert->id,
            'alert_type' => $type,
            'tractor_id' => (string) ($device->tractor?->id ?? ''),
        ]);

        AlertCreated::dispatch($alert, $recipients->pluck('id')->all());
    }

    /**
     * Get all users who should receive an alert for this device's tractor:
     * - super-admin, sub-admin (always)
     * - TPS users in the tractor's groups
     * - FCA users who have the tractor distributed to them
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function getAlertRecipients(Device $device): \Illuminate\Support\Collection
    {
        $tractor = $device->tractor;

        $admins = User::role(['super-admin', 'sub-admin'])->where('is_active', true)->get();

        if (! $tractor) {
            return $admins;
        }

        $tpsUsers = User::role('tps')
            ->where('is_active', true)
            ->whereHas('groups', fn ($q) => $q->whereIn(
                'tractor_groups.id',
                $tractor->groups()->pluck('tractor_groups.id')
            ))
            ->get();

        $fcaUsers = User::role('fca')
            ->where('is_active', true)
            ->whereHas('receivedDistributions', fn ($q) => $q->where('tractor_id', $tractor->id)
                ->where('status', 'distributed'))
            ->get();

        return $admins->merge($tpsUsers)->merge($fcaUsers)->unique('id');
    }

    /**
     * Send FCM push notification to users with tokens.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     */
    private function sendFcmPush(\Illuminate\Support\Collection $users, string $title, string $body, array $data = []): void
    {
        $tokens = $users->pluck('fcm_token')->filter()->unique()->values()->all();

        if (empty($tokens)) {
            return;
        }

        $serverKey = config('services.firebase.server_key');
        if (! $serverKey) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);
        } catch (\Exception $e) {
            Log::warning("SyncJimiAlarms FCM push failed: {$e->getMessage()}");
        }
    }
}
