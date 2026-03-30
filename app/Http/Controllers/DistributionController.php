<?php

namespace App\Http\Controllers;

use App\Events\DistributionCreated;
use App\Http\Requests\StoreDistributionRequest;
use App\Models\TractorDistribution;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistributionController extends Controller
{
    public function index(Request $request)
    {
        $distributions = TractorDistribution::with(['tractor', 'distributedToUser', 'distributedByUser'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Distributions/Index', [
            'distributions' => $distributions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Distributions/Create', [
            'tractors' => \App\Models\Tractor::get(['id', 'no_plate', 'brand', 'model']),
            'fcaUsers' => User::role('fca')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreDistributionRequest $request)
    {
        $data = $request->validated();
        $data['distributed_by'] = $request->user()->id;
        $data['status'] = 'active';

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
        $distribution->load(['tractor.device.latestLocation', 'distributedToUser', 'distributedByUser']);

        return Inertia::render('Distributions/Show', [
            'distribution' => $distribution,
        ]);
    }

    public function returnTractor(Request $request, TractorDistribution $distribution)
    {
        abort_unless($distribution->status === 'active', 422, 'Distribution is not active.');

        $distribution->update([
            'status' => 'returned',
            'return_date' => now(),
        ]);

        return back()->with('success', 'Tractor marked as returned.');
    }

    public function destroy(TractorDistribution $distribution)
    {
        $distribution->delete();

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution record deleted.');
    }
}
