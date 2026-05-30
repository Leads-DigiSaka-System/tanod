<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = TractorGroup::withCount(['tractors', 'tpsUsers as tps_count'])
            ->with(['tpsUsers:id,name,email', 'tractors.device.latestLocation'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('area', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Map tractor online status for the frontend
        $groups->through(function ($group) {
            $group->tractors->transform(function ($tractor) {
                $tractor->is_online = $tractor->device?->latestLocation?->status === 1;

                return $tractor;
            });

            return $group;
        });

        $tractors = Tractor::with('device.latestLocation')
            ->select('id', 'no_plate', 'brand', 'model', 'imei', 'device_id')
            ->get()
            ->map(fn (Tractor $t) => [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'is_online' => $t->device?->latestLocation?->status === 1,
            ]);

        $tpsUsers = User::role('tps')->select('id', 'name', 'email')->get();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'tractors' => $tractors,
            'tpsUsers' => $this->assignableTpsUsers(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $tractors = Tractor::with('device.latestLocation')
            ->select('id', 'no_plate', 'brand', 'model', 'imei', 'device_id')
            ->get()
            ->map(fn (Tractor $t) => [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'is_online' => $t->device?->latestLocation?->status === 1,
            ]);

        return Inertia::render('Groups/Create', [
            'tractors' => $tractors,
            'tpsUsers' => $this->assignableTpsUsers(),
        ]);
    }

    public function store(StoreGroupRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $tractorIds = $data['tractor_ids'] ?? [];
        $tpsUserIds = $data['tps_user_ids'] ?? [];
        $assignAllTps = $request->boolean('assign_all_tps');
        unset($data['tractor_ids'], $data['tps_user_ids'], $data['assign_all_tps']);

        $group = TractorGroup::create($data);
        $group->tractors()->sync($tractorIds);
        $this->syncTpsUsers($group, $tpsUserIds, $assignAllTps);

        return redirect()->route('groups.index')
            ->with('success', 'Group created successfully.');
    }

    public function show(TractorGroup $group)
    {
        $group->load(['tractors.device.latestLocation', 'tpsUsers']);

        return Inertia::render('Groups/Show', [
            'group' => $group,
        ]);
    }

    public function edit(TractorGroup $group)
    {
        $group->load(['tractors', 'tpsUsers']);

        $tractors = Tractor::with('device.latestLocation')
            ->select('id', 'no_plate', 'brand', 'model', 'imei', 'device_id')
            ->get()
            ->map(fn (Tractor $t) => [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'is_online' => $t->device?->latestLocation?->status === 1,
            ]);

        return Inertia::render('Groups/Edit', [
            'group' => $group,
            'tractors' => $tractors,
            'tpsUsers' => $this->assignableTpsUsers(),
        ]);
    }

    public function update(Request $request, TractorGroup $group)
    {
        $data = $request->validate([
            'name' => "required|string|max:255|unique:tractor_groups,name,{$group->id}",
            'description' => 'nullable|string|max:1000',
            'area' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'tractor_ids' => 'nullable|array',
            'tractor_ids.*' => 'exists:tractors,id',
            'assign_all_tps' => 'boolean',
            'tps_user_ids' => 'nullable|array',
            'tps_user_ids.*' => 'exists:users,id',
        ]);

        $tractorIds = $data['tractor_ids'] ?? [];
        $tpsUserIds = $data['tps_user_ids'] ?? [];
        $assignAllTps = $request->boolean('assign_all_tps');
        unset($data['tractor_ids'], $data['tps_user_ids'], $data['assign_all_tps']);

        $group->update($data);
        $group->tractors()->sync($tractorIds);
        $this->syncTpsUsers($group, $tpsUserIds, $assignAllTps);

        return redirect()->route('groups.index')
            ->with('success', 'Group updated successfully.');
    }

    public function destroy(TractorGroup $group)
    {
        $group->tractors()->detach();
        $group->users()->detach();
        $group->delete();

        return redirect()->route('groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    /**
     * @param  array<int>  $requestedTpsUserIds
     */
    private function syncTpsUsers(TractorGroup $group, array $requestedTpsUserIds, bool $assignAllTps): void
    {
        $tpsUserIds = User::role('tps')
            ->where('tps_assign_all_tractors', false)
            ->when(! $assignAllTps, fn ($query) => $query->whereIn('id', $requestedTpsUserIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $tpsPivot = collect($tpsUserIds)
            ->mapWithKeys(fn (int $id) => [$id => ['role' => 'tps']])
            ->all();

        $group->users()->sync($tpsPivot);
    }

    private function assignableTpsUsers()
    {
        return User::role('tps')
            ->where('tps_assign_all_tractors', false)
            ->select('id', 'name', 'email')
            ->get();
    }
}
