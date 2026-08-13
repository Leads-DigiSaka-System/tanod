<?php

namespace App\Http\Controllers;

use App\Events\DistributionCreated;
use App\Exports\DistributionsExport;
use App\Http\Requests\StoreDistributionRequest;
use App\Http\Requests\UpdateDistributionRequest;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DistributionController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'distribution_date');
        $direction = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'area', 'distribution_date', 'status'];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'distribution_date';
        }
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $distributions = TractorDistribution::with(['tractor', 'distributedToUser.fcaProfile', 'distributedByUser', 'tpsUser'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->province, fn ($q, $p) => $q->where('area', 'like', "%{$p}%"))
            ->when($request->region, function ($q, $regionNumber) {
                $regionCode = DB::table('philippine_regions')
                    ->where('region_number', $regionNumber)
                    ->value('region_code');

                if ($regionCode) {
                    $provinces = DB::table('philippine_provinces')
                        ->where('region_code', $regionCode)
                        ->pluck('province_description')
                        ->toArray();

                    if (! empty($provinces)) {
                        $q->where(function ($q) use ($provinces) {
                            foreach ($provinces as $province) {
                                $q->orWhere('area', 'like', "%{$province}%");
                            }
                        });
                    }
                }
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%"))
                    ->orWhereHas('distributedToUser', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('organization_name', 'like', "%{$s}%"))
                    ->orWhere('area', 'like', "%{$s}%");
            }))
            ->orderBy($sort, $direction)
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $provinces = TractorDistribution::whereNotNull('area')
            ->where('area', '!=', '')
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        $regions = DB::table('philippine_regions')
            ->orderBy('region_number')
            ->pluck('region_number');

        return Inertia::render('Distributions/Index', [
            'distributions' => $distributions,
            'filters' => $request->only(['search', 'status', 'province', 'region', 'sort', 'direction', 'per_page']),
            'provinces' => $provinces,
            'regions' => $regions,
            'tractors' => Tractor::with('device.latestLocation')->orderBy('no_plate')->get(['id', 'no_plate', 'brand', 'model', 'device_id']),
            'fcaUsers' => User::role('fca')->where('is_active', true)->get(['id', 'name', 'email']),
            'tpsUsers' => User::role('tps')->where('is_active', true)->get(['id', 'name', 'email']),
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('distribution_ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No distributions selected for export.');
        }

        return Excel::download(
            new DistributionsExport($ids),
            'distributions-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function create()
    {
        return redirect()->route('distributions.index');
    }

    public function store(StoreDistributionRequest $request)
    {
        $data = $request->validated();
        $data['distributed_by'] = ! empty($data['tps_id']) ? $data['tps_id'] : $request->user()->id;
        $data['status'] = 'distributed';
        $data['tractor_id'] = $data['tractor_ids'][0] ?? null;

        if ($request->hasFile('proof_photo')) {
            $data['proof_photo'] = $request->file('proof_photo')->store('distributions/proofs', 'public');
        }

        $distribution = TractorDistribution::create($data);

        $recipientIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->merge([$distribution->distributed_to])
            ->unique()
            ->all();

        DistributionCreated::dispatch($distribution, $recipientIds);

        return redirect()->route('distributions.index')
            ->with('success', 'Tractor distribution recorded.');
    }

    public function show(TractorDistribution $distribution)
    {
        $distribution->load(['tractor.device.latestLocation', 'distributedToUser', 'distributedByUser', 'tpsUser']);

        return Inertia::render('Distributions/Show', [
            'distribution' => $distribution,
        ]);
    }

    public function edit(TractorDistribution $distribution)
    {
        $distribution->load(['tractor', 'distributedToUser', 'distributedByUser', 'tpsUser']);

        return Inertia::render('Distributions/Index', [
            'distributions' => TractorDistribution::with(['tractor', 'distributedToUser', 'distributedByUser', 'tpsUser'])
                ->latest()
                ->paginate(15),
            'filters' => [],
            'tractors' => Tractor::with('device.latestLocation')->orderBy('no_plate')->get(['id', 'no_plate', 'brand', 'model', 'device_id']),
            'fcaUsers' => User::role('fca')->where('is_active', true)->get(['id', 'name', 'email']),
            'tpsUsers' => User::role('tps')->where('is_active', true)->get(['id', 'name', 'email']),
            'editDistribution' => $distribution,
        ]);
    }

    public function update(UpdateDistributionRequest $request, TractorDistribution $distribution)
    {
        $data = $request->validated();
        $data['distributed_by'] = ! empty($data['tps_id']) ? $data['tps_id'] : $distribution->distributed_by;
        $data['tractor_id'] = $data['tractor_ids'][0] ?? null;

        if ($request->hasFile('proof_photo')) {
            $data['proof_photo'] = $request->file('proof_photo')->store('distributions/proofs', 'public');
        }

        $distribution->update($data);

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution updated successfully.');
    }

    public function returnTractor(Request $request, TractorDistribution $distribution)
    {
        abort_unless($distribution->status === 'distributed', 422, 'Distribution is not active.');

        $distribution->update([
            'status' => 'returned',
            'return_date' => now(),
        ]);

        // Clear the name assigned to the tractor during distribution.
        $distribution->tractor?->update(['name' => null]);

        return back()->with('success', 'Tractor marked as returned.');
    }

    public function batchReturn(Request $request)
    {
        $ids = $request->input('distribution_ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No distributions selected.');
        }

        $distributions = TractorDistribution::whereIn('id', $ids)
            ->where('status', 'distributed')
            ->get();

        foreach ($distributions as $distribution) {
            $distribution->update([
                'status' => 'returned',
                'return_date' => now(),
            ]);

            // Clear the name assigned to the tractor during distribution.
            $distribution->tractor?->update(['name' => null]);
        }

        $count = $distributions->count();

        return back()->with('success', "{$count} distribution(s) marked as returned.");
    }

    public function destroy(TractorDistribution $distribution)
    {
        $distribution->delete();

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution record deleted.');
    }
}
