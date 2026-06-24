<?php

namespace App\Http\Controllers;

use App\Exports\TractorUsageExport;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\Device;
use App\Models\Maintenance;
use App\Models\ReportSubscription;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function tractorUsage(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'group_id' => 'nullable|exists:tractor_groups,id',
        ]);

        $query = Tractor::with([
            'groups:id,name',
            'device.latestLocation',
            'maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date'),
        ])->whereHas('device', fn ($q) => $q->notStale());

        if ($request->group_id) {
            $query->whereHas('groups', fn ($q) => $q->where('tractor_groups.id', $request->group_id));
        }

        $tractors = $query->get()->map(function ($t) {
            $loc = $t->device?->latestLocation;
            $distance = $t->total_distance ?? 0;
            $hours = $t->running_hours ?? 0;

            // If implied speed exceeds max tractor speed (40 km/h), hours data is incomplete
            if ($distance > 0 && ($hours <= 0 || $distance / $hours > 40)) {
                $hours = round($distance / 15, 2);
            }

            // PMS schedule: every 100 hrs (100, 200, 300...)
            $maintenancesDone = $t->maintenances->count();
            $pmsCount = $hours > 0 ? (int) floor($hours / 100) : 0;
            if ($hours == 0) {
                $pmsStatus = 'No Data';
            } elseif ($pmsCount > $maintenancesDone) {
                $pmsStatus = 'Due';
            } else {
                $nextPms = ($maintenancesDone + 1) * 100;
                $hrsLeft = round($nextPms - $hours, 1);
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft.' hrs left';
            }

            $lastPms = $t->maintenances->first();

            return [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'group' => $t->groups->first(),
                'total_distance' => $distance,
                'running_hours' => $hours,
                'pms_count' => $pmsCount,
                'status' => $loc ? ($loc->status === 1 ? 'online' : 'offline') : 'inactive',
                'last_pms_date' => $lastPms?->maintenance_date?->format('Y-m-d'),
                'pms_status' => $pmsStatus,
            ];
        });

        $groups = \App\Models\TractorGroup::get(['id', 'name']);

        $pmsDueCount = $tractors->where('pms_status', 'Due')->count();
        $summary = [
            'total_tractors' => $tractors->count(),
            'total_distance' => $tractors->sum('total_distance'),
            'total_hours' => $tractors->sum('running_hours'),
            'avg_usage' => $tractors->count() > 0 ? $tractors->avg('total_distance') : 0,
            'pms_due' => $pmsDueCount,
            'total_maintenances' => Maintenance::count(),
        ];

        return Inertia::render('Reports/TractorUsage', [
            'tractors' => $tractors,
            'groups' => $groups,
            'summary' => $summary,
            'filterData' => $request->only(['from', 'to', 'group_id']),
        ]);
    }

    public function maintenanceSummary(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = Maintenance::with(['tractor', 'performer', 'issueType']);

        if ($request->from && $request->to) {
            $query->whereBetween('maintenance_date', [$request->from, $request->to]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $maintenances = $query->latest('maintenance_date')->paginate(20)->withQueryString();

        $totalCost = Maintenance::where('status', 'completed')->sum('cost');
        $totalCompleted = Maintenance::where('status', 'completed')->count();
        $summary = [
            'total' => Maintenance::count(),
            'completed' => $totalCompleted,
            'pending' => Maintenance::whereIn('status', ['documentation', 'scheduled', 'in_progress'])->count(),
            'total_cost' => round($totalCost, 2),
        ];

        return Inertia::render('Reports/MaintenanceSummary', [
            'maintenances' => $maintenances,
            'summary' => $summary,
            'filterData' => $request->only(['from', 'to', 'status']),
        ]);
    }

    public function bookingSummary(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = Booking::with(['tractor', 'bookedBy']);

        if ($request->from && $request->to) {
            $query->whereBetween('booking_date', [$request->from, $request->to]);
        }

        $bookings = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'total' => Booking::count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Reports/BookingSummary', [
            'bookings' => $bookings,
            'summary' => $summary,
            'filterData' => $request->only(['from', 'to']),
        ]);
    }

    public function deviceStatus()
    {
        $devices = Device::with(['latestLocation', 'tractor:id,device_id,no_plate,brand,model'])
            ->where('is_active', true)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'imei' => $d->imei,
                'device_name' => $d->device_name,
                'tractor' => $d->tractor ? [
                    'brand' => $d->tractor->brand,
                    'model' => $d->tractor->model,
                    'no_plate' => $d->tractor->no_plate,
                ] : null,
                'is_online' => $d->isOnline(),
                'latest_location' => $d->latestLocation ? [
                    'heartbeat_at' => $d->latestLocation->heartbeat_at,
                ] : null,
                'sim' => $d->sim ?? null,
                'expiration_date' => $d->expiration_date ?? null,
            ]);

        $onlineCount = $devices->where('is_online', true)->count();

        return Inertia::render('Reports/DeviceStatus', [
            'devices' => $devices,
            'summary' => [
                'total' => $devices->count(),
                'online' => $onlineCount,
                'offline' => $devices->count() - $onlineCount,
                'active' => $devices->count(),
            ],
        ]);
    }

    public function alertsReport(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'type' => 'nullable|string',
            'acknowledged' => 'nullable|in:0,1',
        ]);

        $query = Alert::with(['tractor:id,no_plate,brand,model', 'device:id,imei,device_name']);

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('acknowledged')) {
            $query->where('is_acknowledged', $request->acknowledged === '1');
        }

        $alerts = $query->latest()->paginate(20)->withQueryString();

        $alertTypes = Alert::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();

        $summary = [
            'total' => Alert::count(),
            'unacknowledged' => Alert::where('is_acknowledged', false)->count(),
            'acknowledged' => Alert::where('is_acknowledged', true)->count(),
            'by_type' => $alertTypes,
        ];

        return Inertia::render('Reports/AlertsHistory', [
            'alerts' => $alerts,
            'summary' => $summary,
            'filterData' => $request->only(['from', 'to', 'type', 'acknowledged']),
        ]);
    }

    public function ticketReport(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
        ]);

        $query = Ticket::with(['tractor:id,no_plate,brand,model', 'submitter:id,name']);

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        $avgResolution = Ticket::whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours');

        $summary = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'avg_resolution_hours' => $avgResolution ? round((float) $avgResolution, 1) : null,
        ];

        return Inertia::render('Reports/TicketSummary', [
            'tickets' => $tickets,
            'summary' => $summary,
            'filterData' => $request->only(['from', 'to', 'status', 'priority']),
        ]);
    }

    public function exportTractorUsage(Request $request)
    {
        $request->validate([
            'group_id' => 'nullable|exists:tractor_groups,id',
        ]);

        $query = Tractor::with([
            'groups:id,name',
            'device.latestLocation',
            'maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date'),
        ])->whereHas('device', fn ($q) => $q->notStale());

        if ($request->group_id) {
            $query->whereHas('groups', fn ($q) => $q->where('tractor_groups.id', $request->group_id));
        }

        $tractors = $query->get()->map(function ($t) {
            $loc = $t->device?->latestLocation;
            $distance = $t->total_distance ?? 0;
            $hours = $t->running_hours ?? 0;

            // If implied speed exceeds max tractor speed (40 km/h), hours data is incomplete
            if ($distance > 0 && ($hours <= 0 || $distance / $hours > 40)) {
                $hours = round($distance / 15, 2);
            }

            // PMS schedule: every 100 hrs (100, 200, 300...)
            $maintenancesDone = $t->maintenances->count();
            $pmsCount = $hours > 0 ? (int) floor($hours / 100) : 0;
            if ($hours == 0) {
                $pmsStatus = 'No Data';
            } elseif ($pmsCount > $maintenancesDone) {
                $pmsStatus = 'Due';
            } else {
                $nextPms = ($maintenancesDone + 1) * 100;
                $hrsLeft = round($nextPms - $hours, 1);
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft.' hrs left';
            }

            $lastPms = $t->maintenances->first();

            return [
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'group' => $t->groups->first(),
                'total_distance' => $distance,
                'running_hours' => $hours,
                'pms_count' => $pmsCount,
                'status' => $loc ? ($loc->status === 1 ? 'online' : 'offline') : 'inactive',
                'last_pms_date' => $lastPms?->maintenance_date?->format('Y-m-d'),
                'pms_status' => $pmsStatus,
            ];
        });

        $pmsDueCount = $tractors->where('pms_status', 'Due')->count();
        $summary = [
            'total_tractors' => $tractors->count(),
            'total_distance' => $tractors->sum('total_distance'),
            'total_hours' => $tractors->sum('running_hours'),
            'pms_due' => $pmsDueCount,
            'total_maintenances' => Maintenance::count(),
        ];

        $filename = 'tractor-usage-report-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new TractorUsageExport($tractors->toArray(), $summary),
            $filename
        );
    }

    public function exportCsv(Request $request)
    {
        $type = $request->get('type', 'tractor-usage');

        return match ($type) {
            'booking-summary' => $this->exportBookingSummary($request),
            'maintenance-summary' => $this->exportMaintenanceSummary($request),
            'device-status' => $this->exportDeviceStatus(),
            'alerts-history' => $this->exportAlertsHistory($request),
            'ticket-summary' => $this->exportTicketSummary($request),
            default => $this->exportTractorUsage($request),
        };
    }

    public function exportBookingSummary(Request $request)
    {
        $query = Booking::with(['tractor:id,no_plate,brand,model', 'bookedBy:id,name']);

        if ($request->from && $request->to) {
            $query->whereBetween('booking_date', [$request->from, $request->to]);
        }

        $bookings = $query->latest()->get()->toArray();

        $summary = [
            'total' => Booking::count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
        ];

        return Excel::download(
            new \App\Exports\BookingSummaryExport($bookings, $summary, $request->only(['from', 'to'])),
            'booking-summary-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportMaintenanceSummary(Request $request)
    {
        $query = Maintenance::with(['tractor:id,no_plate,brand,model', 'performer:id,name', 'issueType:id,name']);

        if ($request->from && $request->to) {
            $query->whereBetween('maintenance_date', [$request->from, $request->to]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $maintenances = $query->latest('maintenance_date')->get()->toArray();

        $summary = [
            'total' => Maintenance::count(),
            'completed' => Maintenance::where('status', 'completed')->count(),
            'pending' => Maintenance::whereIn('status', ['documentation', 'scheduled', 'in_progress'])->count(),
            'total_cost' => round(Maintenance::where('status', 'completed')->sum('cost'), 2),
        ];

        return Excel::download(
            new \App\Exports\MaintenanceSummaryExport($maintenances, $summary, $request->only(['from', 'to', 'status'])),
            'maintenance-summary-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportDeviceStatus()
    {
        $devices = Device::with(['latestLocation', 'tractor:id,device_id,no_plate,brand,model'])
            ->where('is_active', true)
            ->get()
            ->map(fn ($d) => [
                'device_name' => $d->device_name,
                'imei' => $d->imei,
                'tractor' => $d->tractor ? [
                    'brand' => $d->tractor->brand,
                    'model' => $d->tractor->model,
                    'no_plate' => $d->tractor->no_plate,
                ] : null,
                'is_online' => $d->isOnline(),
                'latest_location' => $d->latestLocation ? ['heartbeat_at' => $d->latestLocation->heartbeat_at] : null,
                'sim' => $d->sim ?? null,
                'expiration_date' => $d->expiration_date ?? null,
            ])->toArray();

        $onlineCount = collect($devices)->where('is_online', true)->count();

        return Excel::download(
            new \App\Exports\DeviceStatusExport($devices, [
                'total' => count($devices),
                'online' => $onlineCount,
                'offline' => count($devices) - $onlineCount,
                'active' => count($devices),
            ]),
            'device-status-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportAlertsHistory(Request $request)
    {
        $query = Alert::with(['tractor:id,no_plate', 'device:id,imei,device_name']);

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('acknowledged')) {
            $query->where('is_acknowledged', $request->acknowledged === '1');
        }

        $alerts = $query->latest()->get()->toArray();

        $summary = [
            'total' => Alert::count(),
            'unacknowledged' => Alert::where('is_acknowledged', false)->count(),
            'acknowledged' => Alert::where('is_acknowledged', true)->count(),
        ];

        return Excel::download(
            new \App\Exports\AlertsHistoryExport($alerts, $summary, $request->only(['from', 'to', 'type', 'acknowledged'])),
            'alerts-history-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportTicketSummary(Request $request)
    {
        $query = Ticket::with(['tractor:id,no_plate', 'submitter:id,name']);

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->latest()->get()->map(fn ($t) => [
            'id' => $t->id,
            'subject' => $t->subject,
            'tractor' => $t->tractor ? ['no_plate' => $t->tractor->no_plate] : null,
            'priority' => $t->priority,
            'status' => $t->status,
            'submitter' => $t->submitter ? ['name' => $t->submitter->name] : null,
            'created_at' => $t->created_at?->format('Y-m-d'),
            'resolution_hours' => $t->resolved_at
                ? round($t->created_at->diffInHours($t->resolved_at), 1)
                : null,
        ])->toArray();

        $avgResolution = Ticket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        $summary = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'avg_resolution_hours' => $avgResolution ? round((float) $avgResolution, 1) : null,
        ];

        return Excel::download(
            new \App\Exports\TicketSummaryExport($tickets, $summary, $request->only(['from', 'to', 'status', 'priority'])),
            'ticket-summary-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    // ═══════════════════════════════════════════
    // Report Subscriptions
    // ═══════════════════════════════════════════

    public function subscriptions()
    {
        $subscriptions = ReportSubscription::with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'user_id' => $sub->user_id,
                'user' => $sub->user ? [
                    'id' => $sub->user->id,
                    'name' => $sub->user->name,
                    'email' => $sub->user->email,
                ] : null,
                'report_type' => $sub->report_type,
                'report_type_label' => $sub->reportTypeLabel(),
                'interval' => $sub->interval,
                'interval_label' => $sub->intervalLabel(),
                'day_of_week' => $sub->day_of_week,
                'day_of_month' => $sub->day_of_month,
                'time' => $sub->time,
                'is_active' => $sub->is_active,
                'last_sent_at' => $sub->last_sent_at?->toISOString(),
                'next_scheduled_at' => $sub->next_scheduled_at?->toISOString(),
            ])
            ->values();

        // Group by user for the frontend
        $grouped = $subscriptions->groupBy('user_id')->map(function ($items, $userId) {
            $first = $items->first();

            return [
                'user_id' => $userId,
                'user' => $first['user'],
                'subscriptions' => $items->values(),
            ];
        })->values();

        $users = User::select('id', 'name', 'email')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'sub-admin', 'tps', 'fca']))
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/Index', [
            'groupedSubscriptions' => $grouped,
            'allUsers' => $users,
            'reportTypes' => ReportSubscription::reportTypes(),
            'intervals' => ReportSubscription::intervals(),
            'daysOfWeek' => ReportSubscription::daysOfWeek(),
            'timeOptions' => ReportSubscription::timeOptions(),
        ]);
    }

    public function storeSubscription(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
            'report_types' => 'required|array|min:1',
            'report_types.*' => 'required|in:'.implode(',', array_keys(ReportSubscription::reportTypes())),
            'interval' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|in:'.implode(',', ReportSubscription::daysOfWeek()),
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'time' => 'required|in:'.implode(',', ReportSubscription::timeOptions()),
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['user_ids'] as $userId) {
            foreach ($validated['report_types'] as $reportType) {
                $exists = ReportSubscription::where('user_id', $userId)
                    ->where('report_type', $reportType)
                    ->where('interval', $validated['interval'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $data = [
                    'user_id' => $userId,
                    'report_type' => $reportType,
                    'interval' => $validated['interval'],
                    'day_of_week' => $validated['day_of_week'] ?? null,
                    'day_of_month' => $validated['day_of_month'] ?? null,
                    'time' => $validated['time'],
                    'is_active' => true,
                    'next_scheduled_at' => $this->calculateNextSchedule([
                        'interval' => $validated['interval'],
                        'day_of_week' => $validated['day_of_week'] ?? 'monday',
                        'day_of_month' => $validated['day_of_month'] ?? 1,
                        'time' => $validated['time'],
                    ]),
                ];

                ReportSubscription::create($data);
                $created++;
            }
        }

        $msg = "{$created} subscription(s) created.";
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (already exists).";
        }

        return back()->with('success', $msg);
    }

    public function updateSubscription(Request $request, ReportSubscription $subscription)
    {
        $validated = $request->validate([
            'interval' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|in:'.implode(',', ReportSubscription::daysOfWeek()),
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'time' => 'required|in:'.implode(',', ReportSubscription::timeOptions()),
            'is_active' => 'boolean',
        ]);

        $validated['next_scheduled_at'] = $this->calculateNextSchedule(
            array_merge($subscription->toArray(), $validated)
        );

        $subscription->update($validated);

        return back()->with('success', 'Report subscription updated.');
    }

    public function destroySubscription(ReportSubscription $subscription)
    {
        $subscription->delete();

        return back()->with('success', 'Report subscription removed.');
    }

    public function calculateNextSchedule(array $data): \Illuminate\Support\Carbon
    {
        [$hour, $minute] = explode(':', $data['time']);
        $now = now();
        $next = $now->copy()->setTime((int) $hour, (int) $minute, 0);

        return match ($data['interval']) {
            'daily' => $next->isPast() ? $next->addDay() : $next,
            'weekly' => $this->nextWeekly($next, $data['day_of_week'] ?? 'monday'),
            'monthly' => $this->nextMonthly($next, (int) ($data['day_of_month'] ?? 1)),
            default => $next->addDay(),
        };
    }

    private function nextWeekly(\Illuminate\Support\Carbon $base, string $dayOfWeek): \Illuminate\Support\Carbon
    {
        $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $targetDay = $days[$dayOfWeek] ?? 1;

        $next = $base->copy()->next($targetDay);

        return $next->isPast() ? $next->addWeek() : $next;
    }

    private function nextMonthly(\Illuminate\Support\Carbon $base, int $dayOfMonth): \Illuminate\Support\Carbon
    {
        $next = $base->copy()->setDay(min($dayOfMonth, $base->daysInMonth));

        return $next->isPast() ? $next->addMonth()->setDay(min($dayOfMonth, $next->daysInMonth)) : $next;
    }
}
