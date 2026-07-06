<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TractorResource;
use App\Models\Tractor;
use Illuminate\Http\Request;

class ApiTractorController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));

        $tractors = Tractor::with(['device.latestLocation', 'groups', 'assignee', 'images'])
            ->withSum('trackRecords', 'mileage')
            ->withSum('trackRecords', 'run_time_seconds')
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin']), fn ($q) => $q->whereIn('tractors.id', $user->accessibleTractorIds()))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('no_plate', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhereHas('assignee', fn ($assigneeQuery) => $assigneeQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->group_id, fn ($q, $g) => $q->whereHas('groups', fn ($q) => $q->where('tractor_groups.id', $g)))
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale())
            ->paginate($request->per_page ?? 15);

        return TractorResource::collection($tractors);
    }

    public function show(Request $request, Tractor $tractor)
    {
        abort_unless(in_array($tractor->id, $request->user()->accessibleTractorIds(), true), 403);

        $tractor->loadSum('trackRecords', 'mileage');
        $tractor->loadSum('trackRecords', 'run_time_seconds');
        $tractor->load([
            'device.latestLocation',
            'groups',
            'assignee',
            'images',
            'maintenances' => fn ($q) => $q->latest()->take(5),
        ]);

        return new TractorResource($tractor);
    }

    public function rename(Request $request, Tractor $tractor)
    {
        abort_unless(in_array($tractor->id, $request->user()->accessibleTractorIds(), true), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tractor->update(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Tractor renamed successfully',
            'data' => new TractorResource($tractor->fresh()),
        ]);
    }

    public function updateImplements(Request $request, Tractor $tractor)
    {
        abort_unless(in_array($tractor->id, $request->user()->accessibleTractorIds(), true), 403);

        $validated = $request->validate([
            'id_no' => 'nullable|string|max:255',
            'engine_no' => 'nullable|string|max:255',
            'front_loader_sn' => 'nullable|string|max:255',
            'rotary_tiller_sn' => 'nullable|string|max:255',
            'disc_plow_sn' => 'nullable|string|max:255',
        ]);

        $tractor->update($validated);

        return response()->json([
            'message' => 'Implement details updated successfully',
            'data' => new TractorResource($tractor->fresh()),
        ]);
    }
}
