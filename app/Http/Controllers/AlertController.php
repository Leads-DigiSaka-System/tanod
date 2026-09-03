<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertSummary;
use App\Services\ActivityLogger;
use App\Services\AlertPurgeService;
use App\Services\AlertSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::with(['device', 'tractor', 'geoFence'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->has('acknowledged'), fn ($q) => $q->where('is_acknowledged', $request->boolean('acknowledged')))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('message', 'like', "%{$s}%")
                    ->orWhereHas('device', fn ($q) => $q->where('device_name', 'like', "%{$s}%")->orWhere('imei', 'like', "%{$s}%"))
                    ->orWhereHas('tractor', fn ($q) => $q->where('brand', 'like', "%{$s}%")->orWhere('model', 'like', "%{$s}%")->orWhere('no_plate', 'like', "%{$s}%"));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = AlertSummary::find(1);

        $typeCounts = [];
        foreach (($summary?->by_type ?? []) as $type => $counts) {
            $total = (int) ($counts['total'] ?? 0);
            $unacknowledged = (int) ($counts['unacknowledged'] ?? 0);

            if ($total <= 0 && $unacknowledged <= 0) {
                continue;
            }

            $typeCounts[$type] = [
                'total' => $total,
                'unacknowledged' => $unacknowledged,
            ];
        }

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts,
            'filters' => $request->only(['type', 'acknowledged', 'search']),
            'typeCounts' => $typeCounts,
        ]);
    }

    public function acknowledge(Alert $alert)
    {
        $alert->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        ActivityLogger::log('Alert', $alert->id, 'acknowledged', [
            'type' => $alert->type,
        ], auth()->user());

        return back()->with('success', 'Alert acknowledged.');
    }

    public function acknowledgeAll(Request $request)
    {
        Alert::where('is_acknowledged', false)
            ->update([
                'is_acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->user()->id,
            ]);

        AlertSummaryService::recalculate();

        ActivityLogger::log('Alert', 0, 'acknowledged_all', null, $request->user());

        return back()->with('success', 'All alerts acknowledged.');
    }

    public function purge()
    {
        $deleted = AlertPurgeService::purge(30);

        AlertSummaryService::recalculate();

        ActivityLogger::log('Alert', 0, 'purged_old', [
            'deleted' => $deleted,
        ], request()->user());

        $message = $deleted > 0
            ? $deleted.' '.str('alert')->plural($deleted).' older than 30 days deleted.'
            : 'No alerts older than 30 days to delete.';

        return back()->with('success', $message);
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        ActivityLogger::log('Alert', $alert->id, 'deleted', [
            'type' => $alert->type,
        ], auth()->user());

        return back()->with('success', 'Alert deleted.');
    }
}
