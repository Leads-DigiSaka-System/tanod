<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationLiveTractorRequest;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\Maintenance;
use App\Models\Tractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    public function summary(): JsonResponse
    {
        $activeTractors = Tractor::query()->where('is_active', true)->count();
        $onlineTractors = Tractor::query()
            ->where('is_active', true)
            ->whereHas('device.latestLocation', fn ($query) => $query
                ->where('status', 1)
                ->where('heartbeat_at', '>=', now()->subMinutes(5)))
            ->count();

        return response()->json([
            'data' => [
                'tractors' => [
                    'total' => Tractor::query()->count(),
                    'active' => $activeTractors,
                    'online' => $onlineTractors,
                    'offline_or_stale' => max(0, $activeTractors - $onlineTractors),
                ],
                'devices' => [
                    'total' => Device::query()->count(),
                    'active' => Device::query()->where('is_active', true)->count(),
                ],
                'alerts' => [
                    'unacknowledged' => Alert::query()->where('is_acknowledged', false)->count(),
                    'last_24_hours' => Alert::query()->where('created_at', '>=', now()->subDay())->count(),
                ],
                'maintenance' => [
                    'open' => Maintenance::query()->whereIn('status', ['documentation', 'scheduled', 'in_progress'])->count(),
                    'completed_last_30_days' => Maintenance::query()
                        ->where('status', 'completed')
                        ->where('maintenance_date', '>=', now()->subDays(30)->toDateString())
                        ->count(),
                ],
                'last_location_at' => DeviceLocation::query()->max('heartbeat_at'),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function alertTypes(): JsonResponse
    {
        $types = Alert::query()
            ->selectRaw('type, COUNT(*) as total, SUM(CASE WHEN is_acknowledged = 0 THEN 1 ELSE 0 END) as unacknowledged')
            ->groupBy('type')
            ->orderBy('type')
            ->get()
            ->map(fn (Alert $alert): array => [
                'type' => $alert->type,
                'total' => (int) $alert->getAttribute('total'),
                'unacknowledged' => (int) $alert->getAttribute('unacknowledged'),
            ]);

        return response()->json(['data' => $types]);
    }

    public function liveTractors(IntegrationLiveTractorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $snapshotAt = now();
        $staleAfterSeconds = (int) ($validated['stale_after_seconds'] ?? 300);
        $limit = (int) ($validated['limit'] ?? 1000);
        $cutoff = $snapshotAt->copy()->subSeconds($staleAfterSeconds);
        $search = trim((string) ($validated['search'] ?? ''));

        $onlineLocation = fn (Builder $query): Builder => $query
            ->where('status', 1)
            ->where(function (Builder $timeQuery) use ($cutoff): void {
                $timeQuery->where('heartbeat_at', '>=', $cutoff)
                    ->orWhere(function (Builder $fallbackQuery) use ($cutoff): void {
                        $fallbackQuery->whereNull('heartbeat_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            });

        $query = Tractor::query()
            ->with('device.latestLocation')
            ->withCount(['alerts as unacknowledged_alerts_count' => fn (Builder $alertQuery) => $alertQuery->where('is_acknowledged', false)])
            ->where('is_active', array_key_exists('active', $validated) ? $request->boolean('active') : true)
            ->when($search !== '', function (Builder $tractorQuery) use ($search): void {
                $tractorQuery->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('no_plate', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%");
                });
            })
            ->unless($request->boolean('include_without_location'), fn (Builder $tractorQuery): Builder => $tractorQuery->whereHas('device.latestLocation'))
            ->when(isset($validated['changed_since']), fn (Builder $tractorQuery): Builder => $tractorQuery
                ->whereHas('device.latestLocation', fn (Builder $locationQuery): Builder => $locationQuery->where('updated_at', '>', $validated['changed_since'])))
            ->when(array_key_exists('online', $validated), function (Builder $tractorQuery) use ($onlineLocation, $request): void {
                if ($request->boolean('online')) {
                    $tractorQuery->whereHas('device.latestLocation', $onlineLocation);

                    return;
                }

                $tractorQuery->whereDoesntHave('device.latestLocation', $onlineLocation);
            })
            ->orderBy('id');

        $tractors = $query->limit($limit + 1)->get();
        $truncated = $tractors->count() > $limit;

        $markers = $tractors->take($limit)->map(function (Tractor $tractor) use ($cutoff, $snapshotAt): array {
            $device = $tractor->device;
            $location = $device?->latestLocation;
            $recordedAt = $location?->heartbeat_at ?? $location?->created_at;
            $ageSeconds = $recordedAt ? (int) $recordedAt->diffInSeconds($snapshotAt) : null;
            $isOnline = (int) $location?->status === 1 && $recordedAt?->greaterThanOrEqualTo($cutoff);

            return [
                'tractor' => [
                    'id' => $tractor->id,
                    'name' => $tractor->name,
                    'plate_number' => $tractor->no_plate,
                    'brand' => $tractor->brand,
                    'model' => $tractor->model,
                    'active' => $tractor->is_active,
                ],
                'device' => $device ? [
                    'id' => $device->id,
                    'imei' => $device->imei,
                    'name' => $device->device_name,
                ] : null,
                'position' => $location ? [
                    'latitude' => $location->lat,
                    'longitude' => $location->lng,
                    'speed_kph' => $location->speed,
                    'direction_degrees' => $location->direction,
                    'ignition_on' => (bool) $location->acc_status,
                ] : null,
                'status' => [
                    'online' => $isOnline,
                    'moving' => $isOnline && (float) $location->speed > 0,
                    'stale' => $ageSeconds === null || $recordedAt->lessThan($cutoff),
                    'age_seconds' => $ageSeconds,
                    'recorded_at' => $recordedAt?->toIso8601String(),
                    'changed_at' => $location?->updated_at?->toIso8601String(),
                ],
                'unacknowledged_alerts' => (int) $tractor->unacknowledged_alerts_count,
            ];
        })->values();

        return response()->json([
            'data' => $markers,
            'meta' => [
                'returned' => $markers->count(),
                'online' => $markers->where('status.online', true)->count(),
                'moving' => $markers->where('status.moving', true)->count(),
                'stale' => $markers->where('status.stale', true)->count(),
                'truncated' => $truncated,
                'stale_after_seconds' => $staleAfterSeconds,
                'recommended_poll_interval_seconds' => 15,
                'next_changed_since' => $snapshotAt->toIso8601String(),
            ],
        ])->header('Cache-Control', 'no-store, private');
    }
}
