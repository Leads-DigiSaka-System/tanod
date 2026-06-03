<?php

namespace App\Console\Commands;

use App\Exports\AlertsHistoryExport;
use App\Exports\BookingSummaryExport;
use App\Exports\DeviceStatusExport;
use App\Exports\MaintenanceSummaryExport;
use App\Exports\TicketSummaryExport;
use App\Exports\TractorUsageExport;
use App\Mail\ScheduledReport;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\Device;
use App\Models\Maintenance;
use App\Models\ReportSubscription;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Send scheduled Excel reports to subscribed users';

    public function handle(): int
    {
        $subscriptions = ReportSubscription::with('user')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_scheduled_at')
                    ->orWhere('next_scheduled_at', '<=', now());
            })
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No reports due for sending.');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to process.");

        foreach ($subscriptions as $sub) {
            $this->info("  → {$sub->reportTypeLabel()} to {$sub->user->email}");

            try {
                $excelData = $this->generateReport($sub->report_type, $sub->user);
                $filename = $sub->report_type.'-'.now()->format('Y-m-d').'.xlsx';

                Mail::to($sub->user)->send(new ScheduledReport($sub, $excelData, $filename));

                $sub->update([
                    'last_sent_at' => now(),
                    'next_scheduled_at' => app(\App\Http\Controllers\ReportController::class)
                        ->calculateNextSchedule($sub->toArray()),
                ]);

                $this->info('     ✓ Sent.');
            } catch (\Throwable $e) {
                $this->error("     ✗ Failed: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Get tractor IDs scoped to the user's role.
     * Returns null if user sees all tractors (admin / full-access TPS).
     */
    private function getTractorScopeIds(User $user): ?array
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return null; // all tractors
        }

        if ($user->hasRole('tps') && $user->hasFullTpsTractorAccess()) {
            return null; // full-access TPS
        }

        // TPS with limited access or FCA — only assigned tractors
        $ids = $user->assignedTractors()->pluck('id')->toArray();

        // FCA: also include tractors distributed to their FCA
        if ($user->hasRole('fca') && $user->fca_id) {
            $distributedIds = Tractor::whereHas('distributions', fn ($q) => $q->where('distributed_to', $user->fca_id))
                ->pluck('id')
                ->toArray();
            $ids = array_unique(array_merge($ids, $distributedIds));
        }

        return $ids ?: [-1]; // -1 ensures no results if genuinely empty
    }

    private function scopeQuery($query, string $tractorColumn, ?array $tractorIds): void
    {
        if ($tractorIds !== null) {
            $query->whereIn($tractorColumn, $tractorIds);
        }
    }

    private function generateReport(string $type, User $user): string
    {
        $tractorIds = $this->getTractorScopeIds($user);
        $scopeLabel = $tractorIds === null ? 'all tractors' : count($tractorIds).' tractors';
        $this->info("     Scoping to {$scopeLabel}");

        return match ($type) {
            'tractor-usage' => $this->generateTractorUsage($tractorIds),
            'maintenance-summary' => $this->generateMaintenanceSummary($tractorIds),
            'booking-summary' => $this->generateBookingSummary($tractorIds),
            'device-status' => $this->generateDeviceStatus($tractorIds),
            'alerts-history' => $this->generateAlertsHistory($tractorIds),
            'ticket-summary' => $this->generateTicketSummary($tractorIds),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    private function generateTractorUsage(?array $tractorIds): string
    {
        $query = Tractor::with(['groups:id,name', 'device.latestLocation', 'maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date')]);
        if ($tractorIds !== null) {
            $query->whereIn('id', $tractorIds);
        }

        $tractors = $query->get()->map(function ($t) {
            $distance = $t->total_distance ?? 0;
            $hours = $t->running_hours ?? 0;
            if ($distance > 0 && ($hours <= 0 || $distance / $hours > 40)) {
                $hours = round($distance / 15, 2);
            }
            $maintenancesDone = $t->maintenances->count();
            $pmsCount = $hours > 0 ? (int) floor($hours / 100) : 0;
            $pmsStatus = $hours == 0 ? 'No Data' : ($pmsCount > $maintenancesDone ? 'Due' : 'OK');

            return [
                'no_plate' => $t->no_plate, 'brand' => $t->brand, 'model' => $t->model,
                'imei' => $t->imei, 'group' => $t->groups->first(),
                'total_distance' => $distance, 'running_hours' => $hours,
                'pms_count' => $pmsCount, 'pms_status' => $pmsStatus,
                'status' => 'inactive',
                'last_pms_date' => $t->maintenances->first()?->maintenance_date?->format('Y-m-d'),
            ];
        });

        $summary = [
            'total_tractors' => $tractors->count(),
            'total_distance' => $tractors->sum('total_distance'),
            'total_hours' => $tractors->sum('running_hours'),
            'pms_due' => $tractors->where('pms_status', 'Due')->count(),
            'total_maintenances' => Maintenance::count(),
        ];

        return Excel::raw(new TractorUsageExport($tractors->toArray(), $summary), \Maatwebsite\Excel\Excel::XLSX);
    }

    private function generateMaintenanceSummary(?array $tractorIds): string
    {
        $query = Maintenance::with(['tractor:id,no_plate,brand,model', 'performer:id,name', 'issueType:id,name'])->latest('maintenance_date');
        if ($tractorIds !== null) {
            $query->whereIn('tractor_id', $tractorIds);
        }
        $maintenances = $query->get()->toArray();
        $summary = [
            'total' => count($maintenances),
            'completed' => collect($maintenances)->where('status', 'completed')->count(),
            'pending' => collect($maintenances)->whereIn('status', ['documentation', 'scheduled', 'in_progress'])->count(),
            'total_cost' => round(collect($maintenances)->sum('cost'), 2),
        ];

        return Excel::raw(new MaintenanceSummaryExport($maintenances, $summary), \Maatwebsite\Excel\Excel::XLSX);
    }

    private function generateBookingSummary(?array $tractorIds): string
    {
        $query = Booking::with(['tractor:id,no_plate,brand,model', 'bookedBy:id,name'])->latest();
        if ($tractorIds !== null) {
            $query->whereIn('tractor_id', $tractorIds);
        }
        $bookings = $query->get()->toArray();
        $summary = [
            'total' => count($bookings),
            'approved' => collect($bookings)->where('status', 'approved')->count(),
            'pending' => collect($bookings)->where('status', 'pending')->count(),
            'completed' => collect($bookings)->where('status', 'completed')->count(),
            'rejected' => collect($bookings)->where('status', 'rejected')->count(),
        ];

        return Excel::raw(new BookingSummaryExport($bookings, $summary), \Maatwebsite\Excel\Excel::XLSX);
    }

    private function generateDeviceStatus(?array $tractorIds): string
    {
        $query = Device::with(['latestLocation', 'tractor:id,device_id,no_plate,brand,model'])->where('is_active', true);
        if ($tractorIds !== null) {
            $query->whereHas('tractor', fn ($q) => $q->whereIn('id', $tractorIds));
        }
        $devices = $query->get()->map(fn ($d) => [
            'device_name' => $d->device_name, 'imei' => $d->imei,
            'tractor' => $d->tractor ? ['brand' => $d->tractor->brand, 'model' => $d->tractor->model, 'no_plate' => $d->tractor->no_plate] : null,
            'is_online' => $d->isOnline(),
            'latest_location' => $d->latestLocation ? ['heartbeat_at' => $d->latestLocation->heartbeat_at] : null,
            'sim' => $d->sim, 'expiration_date' => $d->expiration_date,
        ])->toArray();
        $onlineCount = collect($devices)->where('is_online', true)->count();
        $summary = ['total' => count($devices), 'online' => $onlineCount, 'offline' => count($devices) - $onlineCount, 'active' => count($devices)];

        return Excel::raw(new DeviceStatusExport($devices, $summary), \Maatwebsite\Excel\Excel::XLSX);
    }

    private function generateAlertsHistory(?array $tractorIds): string
    {
        $query = Alert::with(['tractor:id,no_plate', 'device:id,imei,device_name'])->latest();
        if ($tractorIds !== null) {
            $query->whereIn('tractor_id', $tractorIds);
        }
        $alerts = $query->get()->toArray();
        $summary = [
            'total' => count($alerts),
            'unacknowledged' => collect($alerts)->where('is_acknowledged', false)->count(),
            'acknowledged' => collect($alerts)->where('is_acknowledged', true)->count(),
        ];

        return Excel::raw(new AlertsHistoryExport($alerts, $summary), \Maatwebsite\Excel\Excel::XLSX);
    }

    private function generateTicketSummary(?array $tractorIds): string
    {
        $query = Ticket::with(['tractor:id,no_plate', 'submitter:id,name'])->latest();
        if ($tractorIds !== null) {
            $query->whereIn('tractor_id', $tractorIds);
        }
        $tickets = $query->get()->map(fn ($t) => [
            'id' => $t->id, 'subject' => $t->subject,
            'tractor' => $t->tractor ? ['no_plate' => $t->tractor->no_plate] : null,
            'priority' => $t->priority, 'status' => $t->status,
            'submitter' => $t->submitter ? ['name' => $t->submitter->name] : null,
            'created_at' => $t->created_at?->format('Y-m-d'),
            'resolution_hours' => $t->resolved_at ? round($t->created_at->diffInHours($t->resolved_at), 1) : null,
        ])->toArray();
        $avgResolution = Ticket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')->value('avg_hours');
        $summary = [
            'total' => count($tickets),
            'open' => collect($tickets)->where('status', 'open')->count(),
            'in_progress' => collect($tickets)->where('status', 'in_progress')->count(),
            'resolved' => collect($tickets)->where('status', 'resolved')->count(),
            'avg_resolution_hours' => $avgResolution ? round((float) $avgResolution, 1) : null,
        ];

        return Excel::raw(new TicketSummaryExport($tickets, $summary), \Maatwebsite\Excel\Excel::XLSX);
    }
}
