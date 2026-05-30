<?php

namespace App\Jobs;

use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\User;
use App\Services\FcmService;
use App\Services\M360SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends PMS notifications (in-app, push, SMS) to the relevant users.
 *
 * - When FCA records PMS → notify TPS assigned to the tractor's group.
 * - When FCA requests TPS help → notify TPS via push + SMS.
 * - When TPS completes PMS → notify the FCA who requested it.
 */
class SendPmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [5, 15, 30];

    public function __construct(
        public int $maintenanceId,
        public string $action, // 'recorded', 'requested', 'completed'
    ) {}

    public function handle(FcmService $fcm, M360SmsService $sms): void
    {
        $maintenance = Maintenance::with(['tractor.groups.tpsUsers', 'requester', 'performer'])
            ->find($this->maintenanceId);

        if (! $maintenance) {
            return;
        }

        $tractor = $maintenance->tractor;
        $label = $tractor->no_plate ?: "{$tractor->brand} {$tractor->model}";

        match ($this->action) {
            'recorded' => $this->notifyTpsOfRecord($maintenance, $tractor, $label, $fcm),
            'requested' => $this->notifyTpsOfRequest($maintenance, $tractor, $label, $fcm, $sms),
            'completed' => $this->notifyFcaOfCompletion($maintenance, $tractor, $label, $fcm),
            default => null,
        };
    }

    /**
     * FCA recorded PMS themselves — notify TPS users (in-app + push only).
     */
    private function notifyTpsOfRecord(Maintenance $maintenance, Tractor $tractor, string $label, FcmService $fcm): void
    {
        $tpsUsers = $this->getTpsUsersForTractor($tractor);
        if ($tpsUsers->isEmpty()) {
            return;
        }

        $title = "PMS Recorded: {$label}";
        $body = "FCA {$maintenance->creator?->name} completed PMS for tractor {$label} at {$maintenance->hours_at_maintenance}h.";

        $this->createNotifications($tpsUsers, 'maintenance', $title, $body, [
            'maintenance_id' => $maintenance->id,
            'tractor_id' => $tractor->id,
            'action' => 'pms_recorded',
        ]);

        $fcm->sendToUsers($tpsUsers, $title, $body, [
            'type' => 'pms_recorded',
            'maintenance_id' => (string) $maintenance->id,
            'tractor_id' => (string) $tractor->id,
        ]);

        Log::info("SendPmsNotification: notified {$tpsUsers->count()} TPS users of PMS record for tractor {$tractor->id}");
    }

    /**
     * FCA requests TPS help — notify TPS via in-app, push, AND SMS.
     */
    private function notifyTpsOfRequest(Maintenance $maintenance, Tractor $tractor, string $label, FcmService $fcm, M360SmsService $sms): void
    {
        $tpsUsers = $this->getTpsUsersForTractor($tractor);
        if ($tpsUsers->isEmpty()) {
            Log::warning("SendPmsNotification: no TPS users found for tractor {$tractor->id}");

            return;
        }

        $requesterName = $maintenance->requester?->name ?? 'An FCA';
        $title = "PMS Assistance Needed: {$label}";
        $body = "{$requesterName} is requesting PMS assistance for tractor {$label}.";
        if ($maintenance->request_notes) {
            $body .= " Note: {$maintenance->request_notes}";
        }

        // In-app notifications
        $this->createNotifications($tpsUsers, 'maintenance', $title, $body, [
            'maintenance_id' => $maintenance->id,
            'tractor_id' => $tractor->id,
            'action' => 'pms_requested',
        ]);

        // FCM push
        $fcm->sendToUsers($tpsUsers, $title, $body, [
            'type' => 'pms_requested',
            'maintenance_id' => (string) $maintenance->id,
            'tractor_id' => (string) $tractor->id,
        ]);

        // SMS to each TPS user with a phone number
        $smsText = "TANOD PMS: {$requesterName} requests PMS help for {$label}.";
        if ($maintenance->request_notes) {
            $smsText .= " Note: {$maintenance->request_notes}";
        }

        foreach ($tpsUsers as $tps) {
            if ($tps->phone) {
                $sms->send($tps->phone, $smsText);
            }
        }

        Log::info("SendPmsNotification: notified {$tpsUsers->count()} TPS users of PMS request for tractor {$tractor->id}");
    }

    /**
     * TPS completed PMS — notify the FCA who requested it (in-app + push).
     */
    private function notifyFcaOfCompletion(Maintenance $maintenance, Tractor $tractor, string $label, FcmService $fcm): void
    {
        $fcaUser = $maintenance->requester ?? $maintenance->creator;
        if (! $fcaUser) {
            return;
        }

        $performerName = $maintenance->performer?->name ?? 'TPS';
        $title = "PMS Completed: {$label}";
        $body = "{$performerName} has completed PMS for tractor {$label}.";

        Notification::create([
            'user_id' => $fcaUser->id,
            'type' => 'maintenance',
            'title' => $title,
            'body' => $body,
            'data' => [
                'maintenance_id' => $maintenance->id,
                'tractor_id' => $tractor->id,
                'action' => 'pms_completed',
            ],
        ]);

        $fcm->sendToUsers(collect([$fcaUser]), $title, $body, [
            'type' => 'pms_completed',
            'maintenance_id' => (string) $maintenance->id,
            'tractor_id' => (string) $tractor->id,
        ]);

        Log::info("SendPmsNotification: notified FCA user {$fcaUser->id} of PMS completion for tractor {$tractor->id}");
    }

    /**
     * Get TPS users assigned to the tractor's groups.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function getTpsUsersForTractor(Tractor $tractor): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('id', User::tpsIdsForTractor($tractor->id))
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Bulk-create in-app notifications for a collection of users.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    private function createNotifications(\Illuminate\Support\Collection $users, string $type, string $title, string $body, array $data): void
    {
        $records = $users->map(fn (User $u) => [
            'user_id' => $u->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => json_encode($data),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        Notification::insert($records);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendPmsNotification failed: {$e->getMessage()}", [
            'maintenance_id' => $this->maintenanceId,
            'action' => $this->action,
        ]);
    }
}
