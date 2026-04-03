<?php

namespace App\Http\Controllers;

use App\Models\Alert;
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

        $typeCounts = Alert::selectRaw('type, count(*) as total, sum(case when is_acknowledged = 0 then 1 else 0 end) as unacknowledged')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn ($item) => [
                'total' => (int) $item->total,
                'unacknowledged' => (int) $item->unacknowledged,
            ]);

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

        return back()->with('success', 'All alerts acknowledged.');
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return back()->with('success', 'Alert deleted.');
    }
}
