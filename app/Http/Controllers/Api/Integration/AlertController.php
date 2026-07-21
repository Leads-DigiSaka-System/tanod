<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationAlertIndexRequest;
use App\Http\Resources\IntegrationAlertResource;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertController extends Controller
{
    public function index(IntegrationAlertIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $alerts = Alert::query()
            ->with(['tractor:id,name,no_plate', 'device:id,imei,device_name'])
            ->when(isset($validated['tractor_id']), function (Builder $query) use ($validated): void {
                $query->where(function (Builder $tractorQuery) use ($validated): void {
                    $tractorQuery->where('tractor_id', $validated['tractor_id'])
                        ->orWhereHas('device.tractor', fn (Builder $relationQuery): Builder => $relationQuery->whereKey($validated['tractor_id']));
                });
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
}
