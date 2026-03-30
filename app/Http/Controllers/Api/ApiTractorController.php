<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TractorResource;
use App\Models\Tractor;
use Illuminate\Http\Request;

class ApiTractorController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tractors = Tractor::with(['device.latestLocation', 'groups', 'assignee', 'images'])
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin']), function ($q) use ($user) {
                if ($user->hasRole('tps')) {
                    $q->whereHas('groups.users', fn ($q) => $q->where('users.id', $user->id));
                } elseif ($user->hasRole('fca')) {
                    $q->whereHas('distributions', fn ($q) => $q->where('distributed_to', $user->id)
                        ->where('status', 'distributed'));
                } elseif ($user->hasRole('farmer')) {
                    $q->whereHas('distributions', fn ($q) => $q->where('distributed_to', $user->fca_id)
                        ->where('status', 'distributed'));
                } else {
                    $q->whereRaw('0 = 1');
                }
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('no_plate', 'like', "%{$s}%")
                    ->orWhere('imei', 'like', "%{$s}%");
            }))
            ->when($request->group_id, fn ($q, $g) => $q->whereHas('groups', fn ($q) => $q->where('tractor_groups.id', $g)))
            ->paginate($request->per_page ?? 15);

        return TractorResource::collection($tractors);
    }

    public function show(Tractor $tractor)
    {
        $tractor->load([
            'device.latestLocation',
            'groups',
            'assignee',
            'images',
            'maintenances' => fn ($q) => $q->latest()->take(5),
        ]);

        return new TractorResource($tractor);
    }
}
