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
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TractorController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        if ($tab === 'deleted' && ! $request->user()->can('tractors.view_deleted')) {
            $tab = 'all';
        }

        $tractors = null;
        $fcaDistributions = null;
        $tpsAssignments = null;
        $deletedTractors = null;

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
                    'organization_name' => $user->organization_name,
                    'barangay' => $user->barangay,
                    'city' => $user->city,
                    'province' => $user->province,
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
        } elseif ($tab === 'deleted') {
            $deletedTractors = Tractor::onlyTrashed()
                ->with(['device.latestLocation', 'groups', 'assignee'])
                ->when($request->deleted_search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('no_plate', 'like', "%{$s}%")
                        ->orWhere('imei', 'like', "%{$s}%")
                        ->orWhere('brand', 'like', "%{$s}%")
                        ->orWhere('name', 'like', "%{$s}%");
                }))
                ->orderBy('deleted_at', 'desc')
                ->paginate($request->input('per_page', 15))
                ->withQueryString();
        } else {
            $sort = $request->get('sort', 'id');
            $direction = $request->get('direction', 'desc');
            $allowedSorts = ['id', 'name', 'no_plate', 'total_distance', 'running_hours'];

            if (! in_array($sort, $allowedSorts)) {
                $sort = 'id';
            }
            if (! in_array($direction, ['asc', 'desc'])) {
                $direction = 'desc';
            }

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
                ->orderBy($sort, $direction)
                ->paginate($request->input('per_page', 15))
                ->withQueryString();
        }

        return Inertia::render('Tractors/Index', [
            'tractors' => $tractors,
            'fcaDistributions' => $fcaDistributions,
            'tpsAssignments' => $tpsAssignments,
            'deletedTractors' => $deletedTractors,
            'filters' => $request->only(['search', 'group_id', 'status', 'sort', 'direction', 'per_page', 'fca_search', 'fca_status', 'tps_search', 'deleted_search', 'tab']),
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

        ActivityLogger::log('Tractor', $tractor->id, 'created', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
        ], $request->user());

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
            'tickets' => fn ($q) => $q->with(['submitter', 'assignee'])->latest()->take(10),
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

        ActivityLogger::log('Tractor', $tractor->id, 'updated', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
            'fields' => array_keys($data),
        ], $request->user());

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

    /**
     * Show the impact of deleting a tractor across all related tables.
     */
    public function deleteCheck(Tractor $tractor)
    {
        $tractor->loadCount([
            'bookings',
            'tickets',
            'distributions',
            'maintenances',
            'alerts',
            'farmAssets',
            'images',
        ]);

        $groupCount = $tractor->groups()->count();

        // FarmerFeedback via tractor_id
        $farmerFeedbackCount = \App\Models\FarmerFeedback::where('tractor_id', $tractor->id)->count();

        // FcaTractorDetail via tractor_id
        $fcaTractorDetailCount = \App\Models\FcaTractorDetail::where('tractor_id', $tractor->id)->count();

        // TractorRecipient via tractor_id
        $tractorRecipientCount = \App\Models\TractorRecipient::where('tractor_id', $tractor->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'bookings_count' => $tractor->bookings_count,
                'tickets_count' => $tractor->tickets_count,
                'distributions_count' => $tractor->distributions_count,
                'maintenances_count' => $tractor->maintenances_count,
                'alerts_count' => $tractor->alerts_count,
                'farm_assets_count' => $tractor->farm_assets_count,
                'images_count' => $tractor->images_count,
                'groups_count' => $groupCount,
                'farmer_feedbacks_count' => $farmerFeedbackCount,
                'fca_tractor_details_count' => $fcaTractorDetailCount,
                'tractor_recipients_count' => $tractorRecipientCount,
                'total_affected' => $tractor->bookings_count
                    + $tractor->tickets_count
                    + $tractor->distributions_count
                    + $tractor->maintenances_count
                    + $tractor->alerts_count
                    + $tractor->farm_assets_count
                    + $tractor->images_count
                    + $groupCount
                    + $farmerFeedbackCount
                    + $fcaTractorDetailCount
                    + $tractorRecipientCount,
            ],
        ]);
    }

    /**
     * Batch delete-check: accepts multiple tractor IDs and returns impact for each.
     */
    public function batchDeleteCheck(Request $request)
    {
        $data = $request->validate([
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'required|exists:tractors,id',
        ]);

        $tractors = Tractor::whereIn('id', $data['tractor_ids'])
            ->withCount([
                'bookings',
                'tickets',
                'distributions',
                'maintenances',
                'alerts',
                'farmAssets',
                'images',
            ])
            ->get(['id', 'no_plate', 'name', 'brand', 'model', 'imei']);

        $results = $tractors->map(function ($tractor) {
            $groupCount = $tractor->groups()->count();
            $farmerFeedbackCount = \App\Models\FarmerFeedback::where('tractor_id', $tractor->id)->count();
            $fcaTractorDetailCount = \App\Models\FcaTractorDetail::where('tractor_id', $tractor->id)->count();
            $tractorRecipientCount = \App\Models\TractorRecipient::where('tractor_id', $tractor->id)->count();

            $total = $tractor->bookings_count
                + $tractor->tickets_count
                + $tractor->distributions_count
                + $tractor->maintenances_count
                + $tractor->alerts_count
                + $tractor->farm_assets_count
                + $tractor->images_count
                + $groupCount
                + $farmerFeedbackCount
                + $fcaTractorDetailCount
                + $tractorRecipientCount;

            return [
                'id' => $tractor->id,
                'no_plate' => $tractor->no_plate,
                'name' => $tractor->name,
                'brand' => $tractor->brand,
                'model' => $tractor->model,
                'imei' => $tractor->imei,
                'bookings_count' => $tractor->bookings_count,
                'tickets_count' => $tractor->tickets_count,
                'distributions_count' => $tractor->distributions_count,
                'maintenances_count' => $tractor->maintenances_count,
                'alerts_count' => $tractor->alerts_count,
                'farm_assets_count' => $tractor->farm_assets_count,
                'images_count' => $tractor->images_count,
                'groups_count' => $groupCount,
                'farmer_feedbacks_count' => $farmerFeedbackCount,
                'fca_tractor_details_count' => $fcaTractorDetailCount,
                'tractor_recipients_count' => $tractorRecipientCount,
                'total_affected' => $total,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Batch delete: accepts multiple tractor IDs and deletes them all.
     */
    public function batchDestroy(Request $request)
    {
        $data = $request->validate([
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'required|exists:tractors,id',
        ]);

        $tractors = Tractor::whereIn('id', $data['tractor_ids'])->get();

        foreach ($tractors as $tractor) {
            $tractor->images->each(function ($img) {
                Storage::disk('public')->delete($img->path);
            });
            $tractor->images()->delete();

            // Delete all alerts (past & present) associated with this tractor
            $tractor->alerts()->delete();

            // Also soft-delete the associated Device so JIMI sync doesn't resurrect it
            if ($tractor->device) {
                $tractor->device->update(['is_active' => false]);
                $tractor->device->delete();
            }

            $tractor->delete();

            ActivityLogger::log('Tractor', $tractor->id, 'deleted', [
                'no_plate' => $tractor->no_plate,
                'name' => $tractor->name,
            ], $request->user());
        }

        $count = count($data['tractor_ids']);

        return redirect()->route('tractors.index')
            ->with('success', $count.' '.str('tractor')->plural($count).' deleted successfully.');
    }

    /**
     * Batch update common fields across multiple tractors.
     */
    public function batchUpdate(Request $request)
    {
        $data = $request->validate([
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'required|exists:tractors,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'maintenance_km' => 'nullable|numeric|min:0',
            'maintenance_hours' => 'nullable|numeric|min:0',
        ]);

        $tractorIds = $data['tractor_ids'];
        unset($data['tractor_ids']);

        // Only update fields that were actually provided (not null/empty)
        $updateData = array_filter($data, fn ($value) => $value !== null && $value !== '');

        if (empty($updateData)) {
            return back()->with('error', 'No fields were provided for update.');
        }

        $tractors = Tractor::whereIn('id', $tractorIds)->get();
        $count = Tractor::whereIn('id', $tractorIds)->update($updateData);

        foreach ($tractors as $tractor) {
            ActivityLogger::log('Tractor', $tractor->id, 'updated', [
                'no_plate' => $tractor->no_plate,
                'name' => $tractor->name,
                'fields' => array_keys($updateData),
            ], $request->user());
        }

        return redirect()->route('tractors.index')
            ->with('success', $count.' '.str('tractor')->plural($count).' updated successfully.');
    }

    /**
     * Sync no_plate values for the selected tractors using the VL103M-{last 5 IMEI} format.
     */
    public function syncNoPlates(Request $request)
    {
        $data = $request->validate([
            'tractor_ids' => 'required|array|min:1',
            'tractor_ids.*' => 'required|exists:tractors,id',
        ]);

        $tractors = Tractor::whereIn('id', $data['tractor_ids'])->get();

        $updated = 0;
        $skipped = 0;

        foreach ($tractors as $tractor) {
            if (empty($tractor->imei) || strlen($tractor->imei) < 5) {
                $skipped++;
                continue;
            }

            $newPlate = 'VL103M-'.substr($tractor->imei, -5);

            if ($tractor->no_plate === $newPlate) {
                continue;
            }

            $oldPlate = $tractor->no_plate;
            $tractor->update(['no_plate' => $newPlate]);
            $updated++;

            ActivityLogger::log('Tractor', $tractor->id, 'no_plate_synced', [
                'old_no_plate' => $oldPlate,
                'no_plate' => $newPlate,
            ], $request->user());
        }

        $message = $updated.' '.str('tractor')->plural($updated).' synced with new no. plate.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' '.str('tractor')->plural($skipped).' skipped (no valid IMEI).';
        }

        return redirect()->route('tractors.index')
            ->with($updated > 0 ? 'success' : 'error', $message);
    }

    public function destroy(Tractor $tractor)
    {
        $tractor->images->each(function ($img) {
            Storage::disk('public')->delete($img->path);
        });
        $tractor->images()->delete();

        // Delete all alerts (past & present) associated with this tractor
        $tractor->alerts()->delete();

        // Also soft-delete the associated Device so JIMI sync doesn't resurrect it
        if ($tractor->device) {
            $tractor->device->update(['is_active' => false]);
            $tractor->device->delete();
        }

        $tractor->delete();

        ActivityLogger::log('Tractor', $tractor->id, 'deleted', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
        ], $request->user());

        return redirect()->route('tractors.index')
            ->with('success', 'Tractor deleted successfully.');
    }

    public function deleteImage(Tractor $tractor, TractorImage $image)
    {
        abort_unless($image->tractor_id === $tractor->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        ActivityLogger::log('Tractor', $tractor->id, 'image_deleted', [
            'no_plate' => $tractor->no_plate,
            'image_path' => $image->path,
        ], request()->user());

        return back()->with('success', 'Image removed.');
    }

    /**
     * Return tractors grouped by duplicated IMEI values.
     */
    public function duplicates()
    {
        $duplicateImeis = Tractor::whereNotNull('imei')
            ->select('imei')
            ->groupBy('imei')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('imei');

        $tractors = Tractor::whereIn('imei', $duplicateImeis)
            ->orderBy('imei')
            ->orderBy('id')
            ->get(['id', 'imei', 'no_plate', 'name', 'brand', 'model', 'created_at']);

        $groups = $tractors->groupBy('imei')->map(fn ($items, $imei) => [
            'imei' => $imei,
            'count' => $items->count(),
            'tractors' => $items->map(fn ($t) => [
                'id' => $t->id,
                'imei' => $t->imei,
                'no_plate' => $t->no_plate,
                'name' => $t->name,
                'brand' => $t->brand,
                'model' => $t->model,
                'created_at' => $t->created_at?->toIso8601String(),
            ])->values(),
        ])->values();

        return response()->json(['data' => $groups]);
    }

    /**
     * Update a tractor's IMEI (used by the duplicate IMEI cleanup modal).
     */
    public function updateImei(Request $request, Tractor $tractor)
    {
        $data = $request->validate([
            'imei' => ['required', 'string', 'max:255', Rule::unique('tractors', 'imei')->ignore($tractor->id)->whereNull('deleted_at')],
        ]);

        $tractor->update(['imei' => $data['imei']]);

        ActivityLogger::log('Tractor', $tractor->id, 'imei_updated', [
            'no_plate' => $tractor->no_plate,
            'imei' => $data['imei'],
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'IMEI updated successfully.',
        ]);
    }

    /**
     * Soft-delete a tractor and return JSON (used by the duplicate IMEI cleanup modal).
     */
    public function quickDelete(Tractor $tractor)
    {
        $tractor->images->each(function ($img) {
            Storage::disk('public')->delete($img->path);
        });
        $tractor->images()->delete();

        $tractor->alerts()->delete();

        if ($tractor->device) {
            $tractor->device->update(['is_active' => false]);
            $tractor->device->delete();
        }

        $tractor->delete();

        ActivityLogger::log('Tractor', $tractor->id, 'deleted', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
        ], request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Tractor deleted successfully.',
        ]);
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

            $tractor = Tractor::find($tractorId);
            if ($tractor) {
                ActivityLogger::log('Tractor', $tractorId, 'distributed', [
                    'no_plate' => $tractor->no_plate,
                    'name' => $tractor->name,
                    'to' => $fcaUser?->name,
                ], $request->user());
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

        // Clear the name assigned to the tractor during distribution.
        $distribution->tractor?->update(['name' => null]);

        $tractor = $distribution->tractor;
        if ($tractor) {
            ActivityLogger::log('Tractor', $tractor->id, 'returned', [
                'no_plate' => $tractor->no_plate,
                'name' => $tractor->name,
            ], request()->user());
        }

        return redirect()->route('tractors.index', ['tab' => 'fca'])
            ->with('success', 'Tractor marked as returned.');
    }

    /**
     * Restore a soft-deleted tractor (and re-activate its device if it was deactivated).
     */
    public function restore($id)
    {
        $tractor = Tractor::onlyTrashed()->findOrFail($id);

        $tractor->restore();

        ActivityLogger::log('Tractor', $tractor->id, 'restored', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
        ], request()->user());

        if ($tractor->device_id) {
            $device = Device::withTrashed()->find($tractor->device_id);
            if ($device && $device->trashed()) {
                $device->restore();
                $device->update(['is_active' => true]);
            }
        }

        return redirect()->route('tractors.index', ['tab' => 'deleted'])
            ->with('success', 'Tractor "'.$tractor->no_plate.'" restored successfully.');
    }

    /**
     * Permanently delete a soft-deleted tractor.
     */
    public function forceDelete($id)
    {
        $tractor = Tractor::onlyTrashed()->findOrFail($id);

        $tractor->images->each(function ($img) {
            Storage::disk('public')->delete($img->path);
        });
        $tractor->images()->delete();

        $tractor->forceDelete();

        ActivityLogger::log('Tractor', $tractor->id, 'force_deleted', [
            'no_plate' => $tractor->no_plate,
            'name' => $tractor->name,
        ], request()->user());

        return redirect()->route('tractors.index', ['tab' => 'deleted'])
            ->with('success', 'Tractor "'.$tractor->no_plate.'" permanently deleted.');
    }
}
