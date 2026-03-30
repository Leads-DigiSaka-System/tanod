<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmerFeedback;
use App\Models\Maintenance;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiTpsController extends Controller
{
    /**
     * Dashboard summary for TPS user.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        return response()->json([
            'tractors_count' => Tractor::whereIn('id', $tractorIds)->count(),
            'open_tickets' => Ticket::whereIn('tractor_id', $tractorIds)->whereIn('status', ['open', 'in_progress'])->count(),
            'pending_maintenance' => Maintenance::whereIn('tractor_id', $tractorIds)->where('status', 'pending')->count(),
            'active_distributions' => TractorDistribution::whereIn('tractor_id', $tractorIds)->where('status', 'distributed')->count(),
        ]);
    }

    /**
     * List tickets for tractors in TPS user's groups.
     */
    public function tickets(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $tickets = Ticket::with(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'assignee:id,name'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn (Builder $q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($tickets);
    }

    /**
     * List maintenances (PMS) for tractors in TPS user's groups.
     */
    public function maintenances(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $maintenances = Maintenance::with(['tractor:id,no_plate,brand,model', 'issueType:id,name', 'performer:id,name'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($maintenances);
    }

    /**
     * List farmer feedbacks for tractors in TPS user's groups.
     */
    public function feedbacks(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $feedbacks = FarmerFeedback::with(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'booking:id,booking_date,purpose'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($feedbacks);
    }

    /**
     * List tractors assigned to TPS user (via groups).
     */
    public function tractors(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $tractors = Tractor::with(['device:id,imei,device_name', 'groups:id,name'])
            ->whereIn('id', $tractorIds)
            ->when($request->filled('search'), fn (Builder $q) => $q->where('no_plate', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($tractors);
    }

    /**
     * List distributions for tractors in TPS user's groups.
     */
    public function distributions(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $distributions = TractorDistribution::with(['tractor:id,no_plate,brand,model', 'distributedToUser:id,name,email'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($distributions);
    }

    /**
     * Get tractor IDs that belong to the TPS user's groups.
     *
     * @return array<int>
     */
    private function tractorIdsForTps(\App\Models\User $user): array
    {
        return Tractor::whereHas('groups.users', fn (Builder $q) => $q->where('users.id', $user->id))
            ->pluck('id')
            ->all();
    }
}
