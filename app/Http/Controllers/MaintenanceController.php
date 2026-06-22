<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\IssueType;
use App\Models\Maintenance;
use App\Models\MaintenanceImage;
use App\Models\Tractor;
use App\Models\TractorRecipient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        // ── Local maintenance records ──
        $localQuery = Maintenance::with(['tractor', 'performedBy', 'issueType'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%"))
                ->orWhere('title', 'like', "%{$s}%"))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->latest()
            ->get();

        $localRows = $localQuery->map(fn (Maintenance $m) => (object) [
            'id' => $m->id,
            'is_recipient' => false,
            'tractor' => (object) [
                'brand' => $m->tractor?->brand,
                'model' => $m->tractor?->model,
                'no_plate' => $m->tractor?->no_plate,
            ],
            'issue_type' => (object) ['name' => $m->issueType?->name],
            'description' => $m->description,
            'status' => $m->status,
            'cost' => $m->cost,
            'maintenance_date' => $m->maintenance_date?->format('Y-m-d'),
            'created_at' => $m->created_at,
            'performedBy' => $m->performedBy ? (object) ['name' => $m->performedBy->name] : null,
        ]);

        // ── Tractor recipients (synced from Digisaka API) ──
        // Each damage_record becomes a maintenance row with the actual issue type
        $recipients = TractorRecipient::latest('source_updated_at')->get();

        $recipientRows = collect();
        foreach ($recipients as $r) {
            $damageRecords = $r->damage_records ?? [];

            if (! empty($damageRecords)) {
                foreach ($damageRecords as $idx => $damage) {
                    $problem = $damage['nature_of_problem'] ?? 'Unreported Issue';
                    $cause = $damage['cause_of_damage'] ?? '';
                    $unit = $damage['unit'] ?? 'Tractor';

                    $recipientRows->push((object) [
                        'id' => 'r-'.$r->source_id.'-d'.($idx + 1),
                        'is_recipient' => true,
                        'is_damage' => true,
                        'recipient_source_id' => $r->source_id,
                        'tractor' => (object) [
                            'brand' => $r->fca ?: $r->tractor_meta_name,
                            'model' => $r->tractor_meta_name,
                            'no_plate' => $r->serial_number ? 'S/N: '.$r->serial_number : '—',
                        ],
                        'issue_type' => (object) ['name' => $unit],
                        'description' => $problem.($cause ? ' — Cause: '.$cause : ''),
                        'status' => ! empty($damage['date_repaired']) ? 'completed' : 'in_progress',
                        'cost' => null,
                        'maintenance_date' => $damage['date_broken'] ?? $r->date_received?->format('Y-m-d'),
                        'created_at' => $r->source_created_at,
                        'updated_at' => $r->source_updated_at ?? $r->source_created_at,
                        'performedBy' => (object) ['name' => $damage['in_charge']['name'] ?? $r->tps_full_name],
                    ]);
                }
            } else {
                // No damage records — show as delivery / machine hours
                $hasMachineHours = ! empty($r->machine_hours);
                $issueName = $hasMachineHours ? 'Machine Hours Update' : 'Delivery & Inspection';
                $desc = $hasMachineHours
                    ? 'Machine hours recorded — '.($r->fca ?: $r->park_address ?: '—')
                    : 'Tractor delivered to '.($r->fca ?: $r->park_address ?: $r->first_name.' '.$r->last_name);

                // All deliveries were completed during field visits
                $status = 'completed';

                $recipientRows->push((object) [
                    'id' => 'r-'.$r->source_id,
                    'is_recipient' => true,
                    'is_damage' => false,
                    'recipient_source_id' => $r->source_id,
                    'tractor' => (object) [
                        'brand' => $r->fca ?: $r->tractor_meta_name,
                        'model' => $r->tractor_meta_name,
                        'no_plate' => $r->serial_number ? 'S/N: '.$r->serial_number : '—',
                    ],
                    'issue_type' => (object) ['name' => $issueName],
                    'description' => $desc,
                    'status' => $status,
                    'cost' => null,
                    'maintenance_date' => $r->date_received?->format('Y-m-d'),
                    'created_at' => $r->source_created_at,
                    'updated_at' => $r->source_updated_at ?? $r->source_created_at,
                    'performedBy' => (object) ['name' => $r->tps_full_name],
                ]);
            }
        }

        // ── Merge & paginate ──
        $allRows = $localRows->concat($recipientRows)
            ->sortByDesc('updated_at')
            ->values();

        $page = (int) $request->get('page', 1);
        $perPage = 15;
        $paginator = new LengthAwarePaginator(
            $allRows->forPage($page, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->only(['search', 'status', 'priority'])]
        );

        return Inertia::render('Maintenance/Index', [
            'maintenances' => $paginator,
            'filters' => $request->only(['search', 'status', 'priority']),
            'tractorRecipients' => $recipients,
            'tractors' => Tractor::get(['id', 'no_plate', 'brand', 'model']),
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
            $path = $image->store('maintenance/'.$maintenance->id, 'public');
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
            $path = $image->store('maintenance/'.$maintenance->id, 'public');
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
