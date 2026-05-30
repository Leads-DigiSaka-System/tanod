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
     * - tps: alerts for tractors in their accessible scope
     * - fca: alerts for tractors distributed to them
     * - farmer: alerts for tractors distributed to their FCA
     *
     * Optional filters: ?type=speed&unacknowledged=true&search=query
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));

        $query = Alert::with(['tractor:id,no_plate,brand,model', 'device:id,device_name,imei'])
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->type))
            ->when($request->boolean('unacknowledged'), fn (Builder $q) => $q->unacknowledged())
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('type', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('device', function (Builder $deviceQuery) use ($search) {
                            $deviceQuery->where('device_name', 'like', "%{$search}%")
                                ->orWhere('imei', 'like', "%{$search}%");
                        })
                        ->orWhereHas('tractor', function (Builder $tractorQuery) use ($search) {
                            $tractorQuery->where('brand', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%")
                                ->orWhere('no_plate', 'like', "%{$search}%");
                        });
                });
            })
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

        $tractorIds = $user->accessibleTractorIds();

        if (empty($tractorIds)) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('tractor_id', $tractorIds);
    }
}
