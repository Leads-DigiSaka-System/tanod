<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePmsRecordRequest;
use App\Jobs\SendPmsNotification;
use App\Models\IssueType;
use App\Models\Maintenance;
use App\Models\MaintenanceImage;
use App\Models\Tractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMaintenanceController extends Controller
{
    /**
     * List PMS records for the user's accessible tractors.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $tractorIds = $this->accessibleTractorIds($user);

        $records = Maintenance::with(['tractor:id,no_plate,brand,model', 'performer:id,name', 'creator:id,name', 'requester:id,name'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->tractor_id, fn ($q, $id) => $q->where('tractor_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($records);
    }

    /**
     * Show a single PMS record.
     */
    public function show(Request $request, Maintenance $maintenance): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $tractorIds = $this->accessibleTractorIds($user);

        abort_unless(in_array($maintenance->tractor_id, $tractorIds), 403);

        $maintenance->load([
            'tractor:id,no_plate,brand,model',
            'performer:id,name',
            'creator:id,name',
            'requester:id,name',
            'images',
        ]);

        return response()->json([
            'data' => $this->formatMaintenance($maintenance),
        ]);
    }

    /**
     * Create a PMS record (self-service) or a PMS request (for TPS help).
     */
    public function store(StorePmsRecordRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $data = $request->validated();
        $type = $data['type']; // 'record' or 'request'

        $tractor = Tractor::withSum('trackRecords', 'mileage')
            ->withSum('trackRecords', 'run_time_seconds')
            ->findOrFail($data['tractor_id']);

        $maintenance = Maintenance::create([
            'tractor_id' => $tractor->id,
            'maintenance_date' => now(),
            'hours_at_maintenance' => $data['hours_at_maintenance'] ?? $tractor->effective_running_hours,
            'km_at_maintenance' => $data['km_at_maintenance'] ?? $tractor->effective_total_distance,
            'pms_checklist' => $data['pms_checklist'] ?? null,
            'description' => $data['description'] ?? null,
            'request_notes' => $data['request_notes'] ?? null,
            'status' => $type === 'record' ? 'completed' : 'scheduled',
            'performed_by' => $type === 'record' ? $user->id : null,
            'created_by' => $user->id,
            'requested_by' => $type === 'request' ? $user->id : null,
        ]);

        // Store images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store("maintenance/{$maintenance->id}", 'public');
                MaintenanceImage::create([
                    'maintenance_id' => $maintenance->id,
                    'path' => $path,
                    'type' => 'before',
                ]);
            }
        }

        // Dispatch notification job
        $action = $type === 'record' ? 'recorded' : 'requested';
        SendPmsNotification::dispatch($maintenance->id, $action);

        $maintenance->load(['tractor:id,no_plate,brand,model', 'performer:id,name', 'creator:id,name', 'images']);

        return response()->json([
            'message' => $type === 'record'
                ? 'PMS record created successfully.'
                : 'PMS assistance request sent.',
            'data' => $this->formatMaintenance($maintenance),
        ], 201);
    }

    /**
     * TPS completes a PMS request.
     */
    public function complete(Request $request, Maintenance $maintenance): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        abort_unless($user->hasRole('tps'), 403, 'Only TPS can complete PMS requests.');
        abort_unless($maintenance->status === 'scheduled', 422, 'This PMS is not in scheduled status.');

        $data = $request->validate([
            'pms_checklist' => ['nullable', 'array'],
            'pms_checklist.*.name' => ['required_with:pms_checklist', 'string'],
            'pms_checklist.*.done' => ['required_with:pms_checklist', 'boolean'],
            'pms_checklist.*.notes' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'conclusion' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $maintenance->update([
            'pms_checklist' => $data['pms_checklist'] ?? $maintenance->pms_checklist,
            'description' => $data['description'] ?? $maintenance->description,
            'conclusion' => $data['conclusion'] ?? null,
            'status' => 'completed',
            'performed_by' => $user->id,
        ]);

        // Store after-images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("maintenance/{$maintenance->id}", 'public');
                MaintenanceImage::create([
                    'maintenance_id' => $maintenance->id,
                    'path' => $path,
                    'type' => 'after',
                ]);
            }
        }

        SendPmsNotification::dispatch($maintenance->id, 'completed');

        $maintenance->load(['tractor:id,no_plate,brand,model', 'performer:id,name', 'creator:id,name', 'images']);

        return response()->json([
            'message' => 'PMS completed successfully.',
            'data' => $this->formatMaintenance($maintenance),
        ]);
    }

    /**
     * Get the PMS checklist items (issue types).
     */
    public function checklistItems(): JsonResponse
    {
        $items = IssueType::where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json(['data' => $items]);
    }

    /**
     * Format a maintenance record for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatMaintenance(Maintenance $m): array
    {
        return [
            'id' => $m->id,
            'tractor_id' => $m->tractor_id,
            'tractor' => $m->relationLoaded('tractor') ? [
                'id' => $m->tractor?->id,
                'no_plate' => $m->tractor?->no_plate,
                'brand' => $m->tractor?->brand,
                'model' => $m->tractor?->model,
            ] : null,
            'maintenance_date' => $m->maintenance_date?->toDateString(),
            'hours_at_maintenance' => $m->hours_at_maintenance,
            'km_at_maintenance' => $m->km_at_maintenance,
            'pms_checklist' => $m->pms_checklist,
            'description' => $m->description,
            'conclusion' => $m->conclusion,
            'request_notes' => $m->request_notes,
            'status' => $m->status,
            'performer' => $m->relationLoaded('performer') && $m->performer ? [
                'id' => $m->performer->id,
                'name' => $m->performer->name,
            ] : null,
            'creator' => $m->relationLoaded('creator') && $m->creator ? [
                'id' => $m->creator->id,
                'name' => $m->creator->name,
            ] : null,
            'requester' => $m->relationLoaded('requester') && $m->requester ? [
                'id' => $m->requester->id,
                'name' => $m->requester->name,
            ] : null,
            'images' => $m->relationLoaded('images') ? $m->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => request()->getSchemeAndHttpHost().'/storage/'.$img->path,
                'type' => $img->type,
            ]) : [],
            'created_at' => $m->created_at,
            'updated_at' => $m->updated_at,
        ];
    }

    /**
     * Get tractor IDs accessible to the user.
     *
     * @return array<int>
     */
    private function accessibleTractorIds(\App\Models\User $user): array
    {
        return $user->accessibleTractorIds();
    }
}
