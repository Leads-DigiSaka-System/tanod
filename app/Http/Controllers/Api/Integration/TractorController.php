<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationAlertIndexRequest;
use App\Http\Requests\IntegrationLocationHistoryRequest;
use App\Http\Requests\IntegrationMaintenanceIndexRequest;
use App\Http\Requests\IntegrationTrackDataRequest;
use App\Http\Requests\IntegrationTractorIndexRequest;
use App\Http\Resources\IntegrationAlertResource;
use App\Http\Resources\IntegrationLocationResource;
use App\Http\Resources\IntegrationMaintenanceResource;
use App\Http\Resources\IntegrationTrackRecordResource;
use App\Http\Resources\IntegrationTractorResource;
use App\Models\Alert;
use App\Models\DeviceTrackRecord;
use App\Models\Tractor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TractorController extends Controller
{
    public function index(IntegrationTractorIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $search = trim((string) ($validated['search'] ?? ''));

        $tractors = Tractor::query()
            ->with(['device.latestLocation', 'assignee', 'groups'])
            ->withSum('trackRecords', 'mileage')
            ->withSum('trackRecords', 'run_time_seconds')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('no_plate', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('engine_no', 'like', "%{$search}%")
                        ->orWhere('chassis_no', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationTractorResource::collection($tractors);
    }

    public function show(string $tractor): IntegrationTractorResource
    {
        $tractor = $this->resolveTractor($tractor);

        $tractor->load([
            'device.latestLocation',
            'assignee',
            'groups',
            'images',
        ]);
        $tractor->loadSum('trackRecords', 'mileage');
        $tractor->loadSum('trackRecords', 'run_time_seconds');

        return new IntegrationTractorResource($tractor);
    }

    public function location(string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);

        $tractor->load('device.latestLocation');
        $device = $tractor->device;
        $location = $device?->latestLocation;

        if (! $device || ! $location) {
            return response()->json([
                'message' => 'No tracking location is available for this tractor.',
                'data' => null,
            ], 404);
        }

        $recordedAt = $location->heartbeat_at ?? $location->created_at;
        $ageSeconds = $recordedAt ? (int) $recordedAt->diffInSeconds(now()) : null;

        return response()->json([
            'data' => [
                'tractor' => [
                    'id' => $tractor->id,
                    'name' => $tractor->name,
                    'plate_number' => $tractor->no_plate,
                ],
                'device' => [
                    'id' => $device->id,
                    'imei' => $device->imei,
                    'name' => $device->device_name,
                ],
                'position' => [
                    'latitude' => $location->lat,
                    'longitude' => $location->lng,
                    'speed_kph' => $location->speed,
                    'direction_degrees' => $location->direction,
                    'gps_satellites' => $location->gps_num,
                    'position_source' => $location->pos_type,
                ],
                'ignition_on' => (bool) $location->acc_status,
                'online' => $device->isOnline(),
                'recorded_at' => $recordedAt?->toIso8601String(),
                'age_seconds' => $ageSeconds,
                'stale' => $ageSeconds === null || $ageSeconds > 300,
            ],
        ]);
    }

    public function alerts(IntegrationAlertIndexRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->safe()->except('tractor_id');

        $alerts = Alert::query()
            ->with(['tractor:id,name,no_plate', 'device:id,imei,device_name'])
            ->where(function (Builder $query) use ($tractor): void {
                $query->where('tractor_id', $tractor->id)
                    ->when($tractor->device_id, fn (Builder $deviceQuery): Builder => $deviceQuery->orWhere('device_id', $tractor->device_id));
            })
            ->when(isset($validated['type']), fn (Builder $query): Builder => $query->where('type', $validated['type']))
            ->when(array_key_exists('acknowledged', $validated), fn (Builder $query): Builder => $query->where('is_acknowledged', $request->boolean('acknowledged')))
            ->when(isset($validated['from']), fn (Builder $query): Builder => $query->where('created_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn (Builder $query): Builder => $query->where('created_at', '<=', $validated['to']))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationAlertResource::collection($alerts);
    }

    public function locationHistory(IntegrationLocationHistoryRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $locations = $device->locations()
            ->when(isset($validated['from']), fn ($query) => $query->where('heartbeat_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->where('heartbeat_at', '<=', $validated['to']))
            ->latest('heartbeat_at')
            ->paginate((int) ($validated['per_page'] ?? 100))
            ->withQueryString();

        return IntegrationLocationResource::collection($locations);
    }

    public function maintenance(IntegrationMaintenanceIndexRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();

        $maintenance = $tractor->maintenances()
            ->with(['issueType:id,name', 'performer:id,name', 'images'])
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['from']), fn ($query) => $query->where('maintenance_date', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->where('maintenance_date', '<=', $validated['to']))
            ->latest('maintenance_date')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationMaintenanceResource::collection($maintenance);
    }

    public function mileage(IntegrationTrackDataRequest $request, string $tractor): JsonResponse
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->safe()->except(['per_page', 'page']);
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $trackRecords = $this->trackRecordsForRange(
            DeviceTrackRecord::query()->where('device_id', $device->id),
            $validated,
        );
        $distanceKilometers = round((float) (clone $trackRecords)->sum('mileage'), 2);
        $runtimeSeconds = (int) (clone $trackRecords)->sum('run_time_seconds');
        $tripCount = (clone $trackRecords)->count();
        $maximumSpeed = round((float) ((clone $trackRecords)->max('max_speed') ?? 0), 2);
        $allTimeStoredDistance = (float) $device->trackRecords()->sum('mileage');
        $allTimeRuntimeSeconds = (int) $device->trackRecords()->sum('run_time_seconds');

        $daily = (clone $trackRecords)
            ->whereNotNull('start_time')
            ->selectRaw('DATE(start_time) as date')
            ->selectRaw('ROUND(SUM(mileage), 2) as mileage_km')
            ->selectRaw('SUM(run_time_seconds) as runtime_seconds')
            ->selectRaw('MAX(max_speed) as maximum_speed_kph')
            ->selectRaw('COUNT(*) as trips')
            ->groupByRaw('DATE(start_time)')
            ->orderBy('date')
            ->get()
            ->map(fn ($day): array => [
                'date' => $day->date,
                'mileage_km' => (float) $day->mileage_km,
                'runtime_seconds' => (int) $day->runtime_seconds,
                'runtime_hours' => round((int) $day->runtime_seconds / 3600, 2),
                'maximum_speed_kph' => (float) $day->maximum_speed_kph,
                'trips' => (int) $day->trips,
            ]);

        return response()->json([
            'data' => [
                'tractor' => [
                    'id' => $tractor->id,
                    'name' => $tractor->name,
                    'plate_number' => $tractor->no_plate,
                ],
                'device' => [
                    'id' => $device->id,
                    'imei' => $device->imei,
                ],
                'range' => [
                    'from' => $validated['from'] ?? null,
                    'to' => $validated['to'] ?? null,
                ],
                'summary' => [
                    'mileage_km' => $distanceKilometers,
                    'runtime_seconds' => $runtimeSeconds,
                    'runtime_hours' => round($runtimeSeconds / 3600, 2),
                    'maximum_speed_kph' => $maximumSpeed,
                    'average_mileage_per_trip_km' => $tripCount > 0 ? round($distanceKilometers / $tripCount, 2) : 0,
                    'trips' => $tripCount,
                ],
                'all_time' => [
                    'odometer_km' => round(max((float) $tractor->total_distance, $allTimeStoredDistance), 2),
                    'running_hours' => round(max((float) $tractor->running_hours, $allTimeRuntimeSeconds / 3600), 2),
                ],
                'daily' => $daily,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function trackData(IntegrationTrackDataRequest $request, string $tractor): AnonymousResourceCollection
    {
        $tractor = $this->resolveTractor($tractor);
        $validated = $request->validated();
        $device = $tractor->device;

        abort_if(! $device, 404, 'This tractor does not have a tracking device.');

        $trackRecords = $this->trackRecordsForRange(
            DeviceTrackRecord::query()->where('device_id', $device->id),
            $validated,
        )
            ->with('device.tractor:id,device_id')
            ->latest('start_time')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return IntegrationTrackRecordResource::collection($trackRecords);
    }

    /**
     * @param  array<string, mixed>  $range
     */
    private function trackRecordsForRange(Builder $query, array $range): Builder
    {
        return $query
            ->when(isset($range['from']), fn (Builder $trackQuery): Builder => $trackQuery
                ->where('start_time', '>=', Carbon::parse($range['from'])->startOfDay()))
            ->when(isset($range['to']), fn (Builder $trackQuery): Builder => $trackQuery
                ->where('start_time', '<=', Carbon::parse($range['to'])->endOfDay()));
    }

    private function resolveTractor(string $identifier): Tractor
    {
        $tractor = Tractor::query()->find($identifier)
            ?? Tractor::query()->where('imei', $identifier)->first();

        if ($tractor) {
            return $tractor;
        }

        $nameMatches = Tractor::query()
            ->where('name', $identifier)
            ->orderBy('id')
            ->limit(2)
            ->get();

        abort_if(
            $nameMatches->count() > 1,
            409,
            'Multiple tractors use this name. Use the tractor ID or IMEI instead.',
        );

        return $nameMatches->firstOrFail();
    }
}
