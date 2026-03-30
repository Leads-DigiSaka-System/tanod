<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiAlertController extends Controller
{
    /**
     * List alerts filtered by the authenticated user's role.
     * - super-admin / sub-admin: all alerts
     * - tps: alerts for tractors in their groups
     * - fca: alerts for tractors distributed to them
     * - farmer: alerts for tractors distributed to their FCA
     *
     * Optional filters: ?type=speed&unacknowledged=true
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Alert::with(['tractor:id,no_plate,brand,model', 'device:id,device_name,imei'])
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->type))
            ->when($request->boolean('unacknowledged'), fn (Builder $q) => $q->unacknowledged())
            ->latest();

        $this->scopeByRole($query, $user);

        $alerts = $query->paginate($request->per_page ?? 20);

        return response()->json($alerts);
    }

    /**
     * Count of unacknowledged alerts for the authenticated user.
     */
    public function unacknowledgedCount(Request $request)
    {
        $user = $request->user();

        $query = Alert::unacknowledged();
        $this->scopeByRole($query, $user);

        return response()->json(['unacknowledged_count' => $query->count()]);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledge(Request $request, Alert $alert)
    {
        $user = $request->user();

        // Verify this user has access to this alert's tractor
        $accessCheck = Alert::where('id', $alert->id);
        $this->scopeByRole($accessCheck, $user);
        abort_unless($accessCheck->exists(), 403);

        $alert->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        return response()->json(['message' => 'Alert acknowledged.']);
    }

    /**
     * Scope alerts by user role via the tractor relationship.
     */
    private function scopeByRole(Builder $query, \App\Models\User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            return;
        }

        if ($user->hasRole('tps')) {
            $query->whereHas('tractor.groups.users', fn (Builder $q) => $q->where('users.id', $user->id));
        } elseif ($user->hasRole('fca')) {
            $query->whereHas('tractor.distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                ->where('status', 'distributed'));
        } elseif ($user->hasRole('farmer')) {
            $query->whereHas('tractor.distributions', fn (Builder $q) => $q->where('distributed_to', $user->fca_id)
                ->where('status', 'distributed'));
        } else {
            $query->whereRaw('0 = 1');
        }
    }
}
