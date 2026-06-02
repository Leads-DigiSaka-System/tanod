<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Booking;
use App\Models\Device;
use App\Models\FarmerFeedback;
use App\Models\GeoFence;
use App\Models\Maintenance;
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
        } elseif ($user->hasRole('tps')) {
            $data = $this->tpsDashboard($user);
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
        $totalTractors = Tractor::count();
        $tractorStatus = $this->liveTractorStatusSummary($totalTractors);
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

        // ── Groups distribution (top 10) ──
        $tractorsByGroup = DB::table('group_tractor')
            ->join('tractor_groups', 'tractor_groups.id', '=', 'group_tractor.tractor_group_id')
            ->select('tractor_groups.name', DB::raw('count(*) as count'))
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

        // ── Machine hours: use cached value only, never block the page load ──
        $totalMachineHours = (float) \Illuminate\Support\Facades\Cache::get('jimi_total_machine_hours', 0);

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
                'totalMachineHours' => $totalMachineHours,
            ],
            'charts' => [
                'tractorStatus' => [
                    'online' => $onlineTractors,
                    'offline' => $offlineTractors,
                    'inactive' => $inactiveTractors,
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
    private function liveTractorStatusSummary(int $totalTractors): array
    {
        $activeDevices = Device::query()
            ->select(['id', 'imei'])
            ->with('tractor:id,device_id')
            ->where('is_active', true)
            ->get();

        $liveLocations = $this->jimiDeviceService->fetchLiveLocations();
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
        $tractors = Tractor::with(['maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date')])->get();

        $totalDistance = 0;
        $totalRunningHours = 0;
        $tractorsWithUsageData = 0;
        $pmsDue = 0;
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
                $pmsStatus = 'no_data';
                $pmsNoData++;
            } elseif ($pmsCount > $maintenancesDone) {
                $pmsStatus = 'due';
                $pmsDue++;
                $pmsDueList->push($t);
            } else {
                $nextPms = ($maintenancesDone + 1) * 100;
                $hrsLeft = round($nextPms - $hours, 1);
                $pmsStatus = $hrsLeft <= 0 ? 'due' : 'ok';
                if ($hrsLeft <= 0) {
                    $pmsDue++;
                    $pmsDueList->push($t);
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
            'pmsOk' => $pmsOk,
            'pmsNoData' => $pmsNoData,
            'totalMaintenanceRecords' => Maintenance::count(),
            'pmsDueList' => $pmsDueList,
        ];
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

    private function tpsDashboard(User $user): array
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
