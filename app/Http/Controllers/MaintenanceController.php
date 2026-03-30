<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\IssueType;
use App\Models\Maintenance;
use App\Models\MaintenanceImage;
use App\Models\Tractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $maintenances = Maintenance::with(['tractor', 'performedBy', 'issueType'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%"))
                ->orWhere('title', 'like', "%{$s}%"))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Maintenance/Index', [
            'maintenances' => $maintenances,
            'filters' => $request->only(['search', 'status', 'priority']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Maintenance/Create', [
            'tractors' => Tractor::get(['id', 'no_plate', 'brand', 'model']),
            'issueTypes' => IssueType::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StoreMaintenanceRequest $request)
    {
        $data = $request->validated();
        $data['performed_by'] = $request->user()->id;

        $images = $data['images'] ?? [];
        unset($data['images']);

        $maintenance = Maintenance::create($data);

        foreach ($images as $i => $image) {
            $path = $image->store('maintenance/' . $maintenance->id, 'public');
            MaintenanceImage::create([
                'maintenance_id' => $maintenance->id,
                'path' => $path,
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('maintenance.show', $maintenance)
            ->with('success', 'Maintenance record created.');
    }

    public function show(Maintenance $maintenance)
    {
        $maintenance->load(['tractor.device', 'performedBy', 'issueType', 'images']);

        return Inertia::render('Maintenance/Show', [
            'maintenance' => $maintenance,
        ]);
    }

    public function edit(Maintenance $maintenance)
    {
        $maintenance->load('images');

        return Inertia::render('Maintenance/Edit', [
            'maintenance' => $maintenance,
            'tractors' => Tractor::get(['id', 'no_plate', 'brand', 'model']),
            'issueTypes' => IssueType::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status' => 'required|in:documentation,scheduled,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'odometer_reading' => 'nullable|numeric|min:0',
            'hours_reading' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $images = $data['images'] ?? [];
        unset($data['images']);

        $maintenance->update($data);

        foreach ($images as $i => $image) {
            $path = $image->store('maintenance/' . $maintenance->id, 'public');
            MaintenanceImage::create([
                'maintenance_id' => $maintenance->id,
                'path' => $path,
                'sort_order' => $maintenance->images()->max('sort_order') + 1 + $i,
            ]);
        }

        return redirect()->route('maintenance.show', $maintenance)
            ->with('success', 'Maintenance record updated.');
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->images->each(fn ($img) => Storage::disk('public')->delete($img->path));
        $maintenance->images()->delete();
        $maintenance->delete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance record deleted.');
    }
}
