<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTractorRequest;
use App\Http\Requests\UpdateTractorRequest;
use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\TractorGroup;
use App\Models\TractorImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TractorController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $tractors = null;
        $fcaDistributions = null;
        $tpsAssignments = null;

        if ($tab === 'fca') {
            $fcaQuery = User::role('fca')
                ->where('is_active', true)
                ->whereHas('receivedDistributions')
                ->with(['receivedDistributions' => fn ($q) => $q
                    ->with(['tractor', 'distributor'])
                    ->when($request->fca_status, fn ($q, $s) => $q->where('status', $s))
                    ->latest(),
                ])
                ->when($request->fca_search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhereHas('receivedDistributions.tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%"));
                }))
                ->orderBy('name')
                ->get();

            $fcaDistributions = $fcaQuery->map(function (User $user) {
                $distributions = $user->receivedDistributions;
                $activeCount = $distributions->where('status', 'distributed')->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'distribution_count' => $distributions->count(),
                    'active_count' => $activeCount,
                    'distributions' => $distributions->values(),
                ];
            })->filter(fn ($u) => $u['distribution_count'] > 0)->values();
        } elseif ($tab === 'tps') {
            $tpsQuery = User::role('tps')
                ->where('is_active', true)
                ->whereHas('groups.tractors')
                ->with(['groups.tractors' => fn ($q) => $q->with(['device.latestLocation', 'groups'])])
                ->when($request->tps_search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhereHas('groups.tractors', fn ($q) => $q->where('no_plate', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%"));
                }))
                ->orderBy('name')
                ->get();

            $tpsAssignments = $tpsQuery->map(function (User $user) {
                $tractors = $user->groups
                    ->flatMap->tractors
                    ->unique('id')
                    ->values();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tractor_count' => $tractors->count(),
                    'tractors' => $tractors,
                ];
            });
        } else {
            $tractors = Tractor::with(['device.latestLocation', 'groups', 'assignee'])
                ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('no_plate', 'like', "%{$s}%")
                        ->orWhere('imei', 'like', "%{$s}%")
                        ->orWhere('brand', 'like', "%{$s}%");
                }))
                ->when($request->group_id, fn ($q, $g) => $q->whereHas('groups', fn ($q) => $q->where('tractor_groups.id', $g)))
                ->when($request->status, function ($q, $status) {
                    if ($status === 'online') {
                        $q->whereHas('device.latestLocation', fn ($q) => $q->where('heartbeat_at', '>=', now()->subMinutes(10)));
                    } elseif ($status === 'offline') {
                        $q->whereDoesntHave('device.latestLocation', fn ($q) => $q->where('heartbeat_at', '>=', now()->subMinutes(10)));
                    }
                })
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return Inertia::render('Tractors/Index', [
            'tractors' => $tractors,
            'fcaDistributions' => $fcaDistributions,
            'tpsAssignments' => $tpsAssignments,
            'filters' => $request->only(['search', 'group_id', 'status', 'fca_search', 'fca_status', 'tps_search', 'tab']),
            'groups' => TractorGroup::where('is_active', true)->get(['id', 'name']),
            'fcaUsers' => User::role('fca')->where('is_active', true)->get(['id', 'name', 'email']),
            'allTractors' => Tractor::with('device.latestLocation')
                ->select('id', 'no_plate', 'brand', 'model', 'device_id')
                ->orderBy('no_plate')
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tractors/Create', [
            'devices' => Device::whereDoesntHave('tractor')->where('is_active', true)->get(['id', 'imei', 'device_name']),
            'users' => User::role('tps')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTractorRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $images = $data['images'] ?? [];
        unset($data['images']);

        $tractor = Tractor::create($data);

        foreach ($images as $i => $image) {
            $path = $image->store('tractors/'.$tractor->id, 'public');
            TractorImage::create([
                'tractor_id' => $tractor->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('tractors.show', $tractor)
            ->with('success', 'Tractor created successfully.');
    }

    public function show(Tractor $tractor)
    {
        $tractor->load([
            'device.latestLocation',
            'groups',
            'assignee',
            'creator',
            'images',
            'maintenances' => fn ($q) => $q->latest()->take(5),
            'bookings' => fn ($q) => $q->latest()->take(5),
            'distributions' => fn ($q) => $q->latest()->take(5),
            'alerts' => fn ($q) => $q->latest()->take(5),
        ]);

        return Inertia::render('Tractors/Show', [
            'tractor' => $tractor,
        ]);
    }

    public function edit(Tractor $tractor)
    {
        $tractor->load('images');

        return Inertia::render('Tractors/Edit', [
            'tractor' => $tractor,
            'devices' => Device::where(function ($q) use ($tractor) {
                $q->whereDoesntHave('tractor')->orWhere('id', $tractor->device_id);
            })->where('is_active', true)->get(['id', 'imei', 'device_name']),
            'users' => User::role('tps')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTractorRequest $request, Tractor $tractor)
    {
        $data = $request->validated();
        $images = $data['images'] ?? [];
        unset($data['images']);

        $tractor->update($data);

        foreach ($images as $i => $image) {
            $path = $image->store('tractors/'.$tractor->id, 'public');
            TractorImage::create([
                'tractor_id' => $tractor->id,
                'path' => $path,
                'sort_order' => $tractor->images()->max('sort_order') + 1 + $i,
            ]);
        }

        return redirect()->route('tractors.show', $tractor)
            ->with('success', 'Tractor updated successfully.');
    }

    public function destroy(Tractor $tractor)
    {
        $tractor->images->each(function ($img) {
            Storage::disk('public')->delete($img->path);
        });
        $tractor->images()->delete();
        $tractor->delete();

        return redirect()->route('tractors.index')
            ->with('success', 'Tractor deleted successfully.');
    }

    public function deleteImage(Tractor $tractor, TractorImage $image)
    {
        abort_unless($image->tractor_id === $tractor->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    public function distribute(Request $request)
    {
        $data = $request->validate([
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'required|exists:tractors,id',
            'distributed_to' => 'required|exists:users,id',
            'area' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'distribution_date' => 'required|date',
        ]);

        $alreadyActive = Tractor::whereIn('id', $data['tractor_ids'])
            ->whereHas('distributions', fn ($q) => $q->where('status', 'distributed'))
            ->pluck('no_plate');

        if ($alreadyActive->isNotEmpty()) {
            return back()->withErrors(['tractor_ids' => 'These tractors already have active distributions: '.$alreadyActive->join(', ')]);
        }

        $fcaUser = User::find($data['distributed_to']);

        foreach ($data['tractor_ids'] as $tractorId) {
            TractorDistribution::create([
                'tractor_id' => $tractorId,
                'distributed_to' => $data['distributed_to'],
                'distributed_by' => $request->user()->id,
                'area' => $data['area'] ?? null,
                'notes' => $data['notes'] ?? null,
                'distribution_date' => $data['distribution_date'],
                'status' => 'distributed',
            ]);

            // Auto-rename tractor to FCA organization name + last 5 digits of IMEI
            if ($fcaUser && ! empty($fcaUser->organization_name)) {
                $tractor = Tractor::find($tractorId);
                if ($tractor && ! empty($tractor->imei)) {
                    $imeiSuffix = substr($tractor->imei, -5);
                    Tractor::where('id', $tractorId)->update(['name' => $fcaUser->organization_name.' ('.$imeiSuffix.')']);
                }
            }
        }

        $count = count($data['tractor_ids']);

        return redirect()->route('tractors.index', ['tab' => 'fca'])
            ->with('success', $count.' '.str('tractor')->plural($count).' distributed to FCA successfully.');
    }

    public function returnDistribution(TractorDistribution $distribution)
    {
        abort_unless($distribution->status === 'distributed', 422, 'Distribution is not active.');

        $distribution->update([
            'status' => 'returned',
            'return_date' => now(),
        ]);

        return redirect()->route('tractors.index', ['tab' => 'fca'])
            ->with('success', 'Tractor marked as returned.');
    }
}
