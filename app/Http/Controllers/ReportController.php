<?php

namespace App\Http\Controllers;

use App\Exports\TractorUsageExport;
use App\Models\Booking;
use App\Models\Device;
use App\Models\DeviceTrackRecord;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Services\Jimi\JimiTrackingService;
use Illuminate\Http\Request;
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
        ]);

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
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft . ' hrs left';
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
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function deviceStatus()
    {
        $devices = Device::with(['latestLocation', 'tractor:id,device_id,no_plate'])
            ->where('is_active', true)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'imei' => $d->imei,
                'name' => $d->device_name,
                'tractor' => $d->tractor?->no_plate,
                'online' => $d->isOnline(),
                'last_heartbeat' => $d->latestLocation?->heartbeat_at,
                'lat' => $d->latestLocation?->lat,
                'lng' => $d->latestLocation?->lng,
            ]);

        return Inertia::render('Reports/DeviceStatus', [
            'devices' => $devices,
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
        ]);

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
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft . ' hrs left';
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

        $filename = 'tractor-usage-report-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new TractorUsageExport($tractors->toArray(), $summary),
            $filename
        );
    }

    public function exportCsv(Request $request)
    {
        // Placeholder for Excel/CSV export using Maatwebsite
        // Will dispatch export job and return download
        return back()->with('info', 'Export feature coming soon.');
    }
}
