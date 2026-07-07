<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Booking;
use App\Models\Device;
use App\Models\FarmerFeedback;
use App\Models\GeoFence;
use App\Models\Maintenance;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use App\Services\Jimi\JimiDeviceService;
use App\Services\Jimi\JimiTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private JimiTrackingService $jimiTracking,
        private JimiDeviceService $jimiDeviceService,
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $data = [];

        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            $data = $this->adminDashboard();
        } elseif ($user->hasRole('tsr')) {
            $data = $this->tsrDashboard($user);
        } elseif ($user->hasRole('fca')) {
            $data = $this->fcaDashboard($user);
        } else {
            $data = $this->farmerDashboard($user);
        }

        return Inertia::render('Dashboard', $data);
    }

    private function adminDashboard(): array
    {
        // ── Tractor status breakdown ──
        $totalTractors = Tractor::whereHas('device', fn ($q) => $q->notStale())->count();
        $liveLocations = $this->jimiDeviceService->fetchLiveLocations();
        $tractorStatus = $this->liveTractorStatusSummary($totalTractors, $liveLocations);
        $onlineTractors = $tractorStatus['onlineTractors'];
        $offlineTractors = $tractorStatus['offlineTractors'];
        $inactiveTractors = $tractorStatus['inactiveTractors'];

        // ── Tractor usage & PMS (report-style 100‑hr schedule) ──
        $usage = $this->tractorUsageSummary();
        $maintenanceDueCount = $usage['pmsDue'];
        $maintenanceDueTractors = $usage['pmsDueList'];

        $maintenanceByStatus = Maintenance::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status')->toArray();

        // ── Alerts ──
        $totalAlerts = Alert::count();
        $unacknowledgedAlerts = Alert::where('is_acknowledged', false)->count();
        $alertsByType = Alert::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')->pluck('count', 'type')->toArray();

        // Alerts trend (last 7 days) — single query
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $alertsTrendRaw = Alert::where('created_at', '>=', $sevenDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $alertsTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $alertsTrend[] = [
                'date' => $day->format('M d'),
                'count' => $alertsTrendRaw[$day->format('Y-m-d')] ?? 0,
            ];
        }

        // ── Bookings ──
        $bookingsByStatus = Booking::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status')->toArray();

        // Bookings trend (last 7 days) — single query
        $bookingsTrendRaw = Booking::where('created_at', '>=', $sevenDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $bookingsTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $bookingsTrend[] = [
                'date' => $day->format('M d'),
                'count' => $bookingsTrendRaw[$day->format('Y-m-d')] ?? 0,
            ];
        }

        // ── Devices ──
        $totalDevices = Device::count();
        $onlineDevices = $tractorStatus['onlineDevices'];

        // ── Device Activation (from Jimi-synced data) ──
        $now = now();
        $activatedDevices = Device::whereNotNull('activation_time')
            ->where(function ($q) use ($now) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', $now);
            })
            ->count();
        $inactivatedDevices = $totalDevices - $activatedDevices;

        // ── Groups ──
        $activeTractorGroups = DB::table('group_tractor')
            ->join('tractor_groups', 'tractor_groups.id', '=', 'group_tractor.tractor_group_id')
            ->distinct('tractor_groups.id')
            ->count('tractor_groups.id');

        // ── Alerts last 7 days total ──
        $totalAlertsLast7Days = (int) array_sum(array_column($alertsTrend, 'count'));

        // ── Usage growth (compare current vs previous period) ──
        $previousPeriodTractorsWithData = Tractor::where('total_distance', '>', 0)
            ->where('updated_at', '<', Carbon::now()->subDays(30))
            ->count();
        $usageGrowthPercent = $previousPeriodTractorsWithData > 0
            ? round((($usage['tractorsWithUsageData'] - $previousPeriodTractorsWithData) / $previousPeriodTractorsWithData) * 100, 1)
            : 0;

        // ── Activation by month (from device activation_time) ──
        $activationByMonthRaw = Device::whereNotNull('activation_time')
            ->select(DB::raw("DATE_FORMAT(activation_time, '%Y-%m') as month"), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        $activationByMonth = [];
        $cursor = Carbon::create(2023, 11, 1);
        $nowMonth = now()->startOfMonth();
        while ($cursor->lte($nowMonth)) {
            $key = $cursor->format('Y-m');
            $activationByMonth[] = [
                'month' => $cursor->format('M Y'),
                'count' => $activationByMonthRaw[$key] ?? 0,
            ];
            $cursor->addMonth();
        }
        $tractorsByGroup = DB::table('group_tractor')
            ->join('tractor_groups', 'tractor_groups.id', '=', 'group_tractor.tractor_group_id')
            ->join('tractors', 'tractors.id', '=', 'group_tractor.tractor_id')
            ->join('devices', 'devices.id', '=', 'tractors.device_id')
            ->joinSub(
                DB::table('device_locations')
                    ->select('device_id', DB::raw('MAX(id) as latest_id'))
                    ->groupBy('device_id'),
                'latest_loc',
                'latest_loc.device_id',
                '=',
                'devices.id'
            )
            ->join('device_locations as dl', 'dl.id', '=', 'latest_loc.latest_id')
            ->where(function ($q) {
                $cutoff = now()->subDays(365);
                $q->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('dl.heartbeat_at')
                        ->where('dl.heartbeat_at', '>=', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('dl.heartbeat_at')
                        ->where('dl.created_at', '>=', $cutoff);
                });
            })
            ->select('tractor_groups.name', DB::raw('count(DISTINCT tractors.id) as count'))
            ->groupBy('tractor_groups.id', 'tractor_groups.name')
            ->orderByDesc('count')
            ->take(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'count' => $row->count,
            ])->values()->toArray();

        // ── Feedback ──
        $totalFeedback = FarmerFeedback::count();

        // ── GeoFences ──
        $totalGeoFences = GeoFence::count();
        $activeGeoFences = GeoFence::where('is_active', true)->count();

        // ── Tickets ──
        $openTickets = Ticket::whereNotIn('status', ['resolved', 'closed'])->count();
        $totalTickets = Ticket::count();

        // ── Machine hours: use cached value only, never block the page load ──
        $totalMachineHours = (float) \Illuminate\Support\Facades\Cache::get('jimi_total_machine_hours', 0);

        // ── Offline breakdown by duration ──
        $offlineBreakdown = $this->offlineBreakdown($liveLocations);

        return [
            'stats' => [
                'totalTractors' => $totalTractors,
                'onlineTractors' => $onlineTractors,
                'offlineTractors' => $offlineTractors,
                'inactiveTractors' => $inactiveTractors,
                'totalDistance' => $usage['totalDistance'],
                'avgDistancePerTractor' => $usage['avgDistancePerTractor'],
                'totalRunningHours' => $usage['totalRunningHours'],
                'avgHoursPerTractor' => $usage['avgHoursPerTractor'],
                'tractorsWithUsageData' => $usage['tractorsWithUsageData'],
                'usageDataPercent' => $usage['usageDataPercent'],
                'pmsDue' => $usage['pmsDue'],
                'pmsOk' => $usage['pmsOk'],
                'pmsNoData' => $usage['pmsNoData'],
                'totalMaintenanceRecords' => $usage['totalMaintenanceRecords'],
                'totalDevices' => $totalDevices,
                'onlineDevices' => $onlineDevices,
                'totalUsers' => User::count(),
                'pendingBookings' => $bookingsByStatus['pending'] ?? 0,
                'maintenanceDue' => $maintenanceDueCount,
                'unacknowledgedAlerts' => $unacknowledgedAlerts,
                'totalAlerts' => $totalAlerts,
                'totalGeoFences' => $totalGeoFences,
                'activeGeoFences' => $activeGeoFences,
                'totalFeedback' => $totalFeedback,
                'openTickets' => $openTickets,
                'totalTickets' => $totalTickets,
                'totalMachineHours' => $totalMachineHours,
                'activatedDevices' => $activatedDevices,
                'inactivatedDevices' => $inactivatedDevices,
                'usageHwData' => $usage['tractorsWithUsageData'],
                'usageGrowthPercent' => $usageGrowthPercent,
                'totalAlertsLast7Days' => $totalAlertsLast7Days,
                'activeGroups' => $activeTractorGroups,
                'offlineLessThanDay' => $offlineBreakdown['lessThanDay'],
                'offline1to7Days' => $offlineBreakdown['oneToSevenDays'],
                'offline7to30Days' => $offlineBreakdown['sevenToThirtyDays'],
                'offline30to100Days' => $offlineBreakdown['thirtyToHundredDays'],
                'offlineMoreThan100Days' => $offlineBreakdown['moreThanHundredDays'],
            ],
            'charts' => [
                'tractorStatus' => [
                    'online' => $onlineTractors,
                    'offline' => $offlineTractors,
                    'inactive' => $inactiveTractors,
                ],
                'offlineBreakdown' => [
                    'lessThanDay' => $offlineBreakdown['lessThanDay'],
                    'oneToSevenDays' => $offlineBreakdown['oneToSevenDays'],
                    'sevenToThirtyDays' => $offlineBreakdown['sevenToThirtyDays'],
                    'thirtyToHundredDays' => $offlineBreakdown['thirtyToHundredDays'],
                    'moreThanHundredDays' => $offlineBreakdown['moreThanHundredDays'],
                ],
                'pmsBreakdown' => [
                    'due' => $usage['pmsDue'],
                    'ok' => $usage['pmsOk'],
                    'noData' => $usage['pmsNoData'],
                ],
                'maintenanceByStatus' => $maintenanceByStatus,
                'alertsByType' => $alertsByType,
                'alertsTrend' => $alertsTrend,
                'bookingsByStatus' => $bookingsByStatus,
                'bookingsTrend' => $bookingsTrend,
                'tractorsByGroup' => $tractorsByGroup,
                'deviceActivation' => [
                    'total' => $totalDevices,
                    'activated' => $activatedDevices,
                    'inactivated' => $inactivatedDevices,
                ],
                'pmsScheduleBreakdown' => [
                    'finished' => $usage['pmsOk'],
                    'upcoming' => $usage['pmsUpcoming'],
                    'due' => $usage['pmsDue'],
                ],
                'activationByMonth' => $activationByMonth,
                'alertsLast7Days' => $totalAlertsLast7Days,
                'activeGroups' => $activeTractorGroups,
            ],
            'recentAlerts' => Alert::with('device', 'tractor')
                ->where('is_acknowledged', false)
                ->latest()
                ->take(5)
                ->get(),
            'recentBookings' => Booking::with('tractor', 'bookedBy')
                ->latest()
                ->take(5)
                ->get(),
            'maintenanceDueList' => $maintenanceDueTractors->take(5)->values(),
        ];
    }

    /**
     * @return array{onlineTractors:int, offlineTractors:int, inactiveTractors:int, onlineDevices:int}
     */
    private function liveTractorStatusSummary(int $totalTractors, array $liveLocations): array
    {
        $activeDevices = Device::query()
            ->select(['id', 'imei'])
            ->with('tractor:id,device_id')
            ->where('is_active', true)
            ->notStale()
            ->get();

        $activeTractorCount = 0;
        $onlineTractors = 0;
        $onlineDevices = 0;

        foreach ($activeDevices as $device) {
            if ($device->tractor !== null) {
                $activeTractorCount++;
            }

            if (! $this->isLiveLocationOnline($liveLocations[$device->imei] ?? null)) {
                continue;
            }

            $onlineDevices++;

            if ($device->tractor !== null) {
                $onlineTractors++;
            }
        }

        return [
            'onlineTractors' => $onlineTractors,
            'offlineTractors' => max($activeTractorCount - $onlineTractors, 0),
            'inactiveTractors' => max($totalTractors - $activeTractorCount, 0),
            'onlineDevices' => $onlineDevices,
        ];
    }

    /**
     * Compute tractor usage metrics and PMS status using the same
     * 100‑hour schedule logic as /reports/tractor-usage.
     *
     * @return array{totalDistance:float, avgDistancePerTractor:float,
     *               totalRunningHours:float, avgHoursPerTractor:float,
     *               tractorsWithUsageData:int, usageDataPercent:float,
     *               pmsDue:int, pmsOk:int, pmsNoData:int,
     *               totalMaintenanceRecords:int, pmsDueList:Collection}
     */
    private function tractorUsageSummary(): array
    {
        $tractors = Tractor::with(['maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date')])
            ->whereHas('device', fn ($q) => $q->notStale())
            ->get();

        $totalDistance = 0;
        $totalRunningHours = 0;
        $tractorsWithUsageData = 0;
        $pmsDue = 0;
        $pmsUpcoming = 0;
        $pmsOk = 0;
        $pmsNoData = 0;
        $pmsDueList = collect();

        foreach ($tractors as $t) {
            $distance = (float) ($t->total_distance ?? 0);
            $hours = (float) ($t->running_hours ?? 0);

            // Fallback: if implied speed > 40 km/h, estimate hours from distance
            if ($distance > 0 && ($hours <= 0 || $distance / $hours > 40)) {
                $hours = round($distance / 15, 2);
            }

            $totalDistance += $distance;
            $totalRunningHours += $hours;

            if ($distance > 0) {
                $tractorsWithUsageData++;
            }

            // PMS schedule: every 100 hrs
            $maintenancesDone = $t->maintenances->count();
            $pmsCount = $hours > 0 ? (int) floor($hours / 100) : 0;

            if ($hours == 0) {
                $pmsNoData++;
            } elseif ($pmsCount > $maintenancesDone) {
                $pmsDue++;
                $pmsDueList->push($t);
            } else {
                $nextPms = ($maintenancesDone + 1) * 100;
                $hrsLeft = round($nextPms - $hours, 1);

                if ($hrsLeft <= 0) {
                    $pmsDue++;
                    $pmsDueList->push($t);
                } elseif ($hrsLeft <= 20) {
                    $pmsUpcoming++;
                } else {
                    $pmsOk++;
                }
            }
        }

        $totalTractors = $tractors->count();

        return [
            'totalDistance' => round($totalDistance, 2),
            'avgDistancePerTractor' => $totalTractors > 0 ? round($totalDistance / $totalTractors, 2) : 0,
            'totalRunningHours' => round($totalRunningHours, 2),
            'avgHoursPerTractor' => $totalTractors > 0 ? round($totalRunningHours / $totalTractors, 2) : 0,
            'tractorsWithUsageData' => $tractorsWithUsageData,
            'usageDataPercent' => $totalTractors > 0 ? round(($tractorsWithUsageData / $totalTractors) * 100, 1) : 0,
            'pmsDue' => $pmsDue,
            'pmsUpcoming' => $pmsUpcoming,
            'pmsOk' => $pmsOk,
            'pmsNoData' => $pmsNoData,
            'totalMaintenanceRecords' => Maintenance::count(),
            'pmsDueList' => $pmsDueList,
        ];
    }

    /**
     * Categorize offline tractors by how long since their last heartbeat.
     *
     * @return array{lessThanDay:int, oneToSevenDays:int,
     *               sevenToThirtyDays:int, thirtyToHundredDays:int,
     *               moreThanHundredDays:int}
     */
    private function offlineBreakdown(array $liveLocations): array
    {
        $result = [
            'lessThanDay' => 0,
            'oneToSevenDays' => 0,
            'sevenToThirtyDays' => 0,
            'thirtyToHundredDays' => 0,
            'moreThanHundredDays' => 0,
        ];

        $activeDevices = Device::query()
            ->select(['id', 'imei'])
            ->with(['tractor:id,device_id', 'latestLocation'])
            ->where('is_active', true)
            ->notStale()
            ->get();

        foreach ($activeDevices as $device) {
            if (! $device->tractor) {
                continue;
            }

            // Already counted as online? Skip.
            if ($this->isLiveLocationOnline($liveLocations[$device->imei] ?? null)) {
                continue;
            }

            $heartbeatAt = $device->latestLocation?->heartbeat_at;

            if (! $heartbeatAt) {
                // No heartbeat ever recorded — treat as long-term offline (>100 days)
                $result['moreThanHundredDays']++;

                continue;
            }

            $daysAgo = (int) $heartbeatAt->diffInDays(now()->utc());

            if ($daysAgo < 1) {
                $result['lessThanDay']++;
            } elseif ($daysAgo < 7) {
                $result['oneToSevenDays']++;
            } elseif ($daysAgo < 30) {
                $result['sevenToThirtyDays']++;
            } elseif ($daysAgo < 100) {
                $result['thirtyToHundredDays']++;
            } else {
                $result['moreThanHundredDays']++;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $apiData
     */
    private function isLiveLocationOnline(?array $apiData): bool
    {
        if (! $apiData) {
            return false;
        }

        // Prefer JIMI's own online/offline determination (status=1 → online).
        // Fall back to heartbeat-age threshold only when status is missing.
        if (array_key_exists('status', $apiData)) {
            return (int) $apiData['status'] === 1;
        }

        $heartbeatAt = $this->parseHeartbeat($apiData['hbTime'] ?? null);

        if (! $heartbeatAt) {
            return false;
        }

        return $heartbeatAt->diffInMinutes(now()->utc()) <= $this->onlineThresholdMinutes();
    }

    private function parseHeartbeat(?string $heartbeatAt): ?Carbon
    {
        if (blank($heartbeatAt)) {
            return null;
        }

        return Carbon::parse($heartbeatAt, 'UTC')->utc();
    }

    private function onlineThresholdMinutes(): int
    {
        return max((int) config('jimi.online_threshold_minutes', 10), 1);
    }

    private function tsrDashboard(User $user): array
    {
        return [
            'stats' => [
                'assignedTractors' => Tractor::where('assigned_to', $user->id)->count(),
                'pendingMaintenance' => Maintenance::where('performed_by', $user->id)
                    ->whereIn('status', ['documentation', 'scheduled'])
                    ->count(),
                'activeDistributions' => $user->distributions()->where('status', 'active')->count(),
            ],
            'myTractors' => Tractor::where('assigned_to', $user->id)
                ->with('device.latestLocation')
                ->get(),
            'pendingTasks' => Maintenance::where('performed_by', $user->id)
                ->whereIn('status', ['documentation', 'scheduled', 'in_progress'])
                ->with('tractor')
                ->latest()
                ->take(10)
                ->get(),
        ];
    }

    private function fcaDashboard(User $user): array
    {
        return [
            'stats' => [
                'pendingBookings' => Booking::where('status', 'pending')->count(),
                'approvedBookings' => Booking::where('approved_by', $user->id)
                    ->where('status', 'approved')
                    ->count(),
                'totalFeedback' => FarmerFeedback::count(),
            ],
            'pendingBookings' => Booking::with('tractor', 'bookedBy')
                ->where('status', 'pending')
                ->latest()
                ->take(10)
                ->get(),
            'recentFeedback' => FarmerFeedback::with('tractor', 'submitter')
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function farmerDashboard(User $user): array
    {
        return [
            'stats' => [
                'myBookings' => $user->bookings()->count(),
                'activeBookings' => $user->bookings()->whereIn('status', ['approved', 'in_use'])->count(),
                'pendingBookings' => $user->bookings()->where('status', 'pending')->count(),
            ],
            'myBookings' => $user->bookings()
                ->with('tractor')
                ->latest()
                ->take(10)
                ->get(),
            'availableTractors' => Tractor::whereHas('device', fn ($q) => $q->where('is_active', true))
                ->with('device.latestLocation')
                ->take(10)
                ->get(),
        ];
    }
}
