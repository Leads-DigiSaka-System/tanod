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

        // ── PMS (Preventive Maintenance) — single query, filter once ──
        $allTractorsForMaintenance = Tractor::with(['device', 'maintenances' => fn ($q) => $q->where('status', 'completed')->latest('maintenance_date')])->get();
        $maintenanceDueTractors = $allTractorsForMaintenance->filter(fn ($t) => $t->isMaintenanceDue());
        $maintenanceDueCount = $maintenanceDueTractors->count();

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
     * @param  array<string, mixed>|null  $apiData
     */
    private function isLiveLocationOnline(?array $apiData): bool
    {
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
