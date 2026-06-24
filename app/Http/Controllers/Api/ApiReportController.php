<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FarmerFeedback;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiReportController extends Controller
{
    /**
     * Return role-appropriate report summaries.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $sections = [];

        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            $sections = $this->adminReport();
        } elseif ($user->hasRole('fca')) {
            $sections = $this->fcaReport($user);
        } elseif ($user->hasRole('tps')) {
            $sections = $this->tpsReport($user);
        } elseif ($user->hasRole('farmer')) {
            $sections = $this->farmerReport($user);
        }

        return response()->json(['data' => $sections]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function adminReport(): array
    {
        return [
            $this->userOverview(),
            $this->bookingSummary(Booking::query()),
            // Exclude tractors with devices stale >365 days
            $this->tractorUsage(Tractor::query()->whereHas('device', fn ($q) => $q->notStale())),
            $this->maintenanceSummary(Maintenance::query()),
            $this->feedbackSummary(FarmerFeedback::query()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fcaReport(User $user): array
    {
        $farmerIds = $user->farmers()->pluck('id')->all();
        $ownAndFarmerIds = array_merge([$user->id], $farmerIds);

        $tractorIds = Tractor::whereHas(
            'distributions',
            fn (Builder $q) => $q->where('distributed_to', $user->id)->where('status', 'distributed'),
        )->pluck('id')->all();

        $bookingQuery = Booking::where(function ($q) use ($user, $farmerIds) {
            $q->where('booked_by', $user->id)
                ->orWhereIn('farmer_id', $farmerIds);
        });

        $maintenanceQuery = Maintenance::whereIn('tractor_id', $tractorIds);
        $feedbackQuery = FarmerFeedback::whereIn('tractor_id', $tractorIds);
        $tractorQuery = Tractor::whereIn('id', $tractorIds)
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale());

        return [
            $this->farmerOverview($user),
            $this->bookingSummary($bookingQuery),
            $this->tractorUsage($tractorQuery),
            $this->maintenanceSummary($maintenanceQuery),
            $this->feedbackSummary($feedbackQuery),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tpsReport(User $user): array
    {
        $tractorIds = $user->accessibleTractorIds();

        $bookingQuery = Booking::whereIn('tractor_id', $tractorIds);
        $maintenanceQuery = Maintenance::whereIn('tractor_id', $tractorIds);
        $feedbackQuery = FarmerFeedback::whereIn('tractor_id', $tractorIds);
        $tractorQuery = Tractor::whereIn('id', $tractorIds)
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale());

        return [
            $this->bookingSummary($bookingQuery),
            $this->tractorUsage($tractorQuery),
            $this->maintenanceSummary($maintenanceQuery),
            $this->feedbackSummary($feedbackQuery),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function farmerReport(User $user): array
    {
        $bookingQuery = Booking::where(function ($q) use ($user) {
            $q->where('booked_by', $user->id)
                ->orWhere('farmer_id', $user->id);
        });

        $feedbackQuery = FarmerFeedback::where('submitted_by', $user->id);

        return [
            $this->bookingSummary($bookingQuery),
            $this->feedbackSummary($feedbackQuery),
        ];
    }

    // ─── Section Builders ────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function userOverview(): array
    {
        return [
            'title' => 'User Overview',
            'icon' => 'people',
            'rows' => [
                ['label' => 'Total Users', 'value' => User::where('is_active', true)->count()],
                ['label' => 'FCA', 'value' => User::role('fca')->where('is_active', true)->count()],
                ['label' => 'TPS', 'value' => User::role('tps')->where('is_active', true)->count()],
                ['label' => 'Farmers', 'value' => User::role('farmer')->where('is_active', true)->count()],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function farmerOverview(User $fca): array
    {
        $farmers = $fca->farmers();

        return [
            'title' => 'Farmer Overview',
            'icon' => 'people',
            'rows' => [
                ['label' => 'Total Farmers', 'value' => $farmers->count()],
                ['label' => 'Active Farmers', 'value' => $farmers->where('is_active', true)->count()],
                ['label' => 'Distributed Tractors', 'value' => TractorDistribution::where('distributed_to', $fca->id)->where('status', 'distributed')->count()],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingSummary(Builder $query): array
    {
        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $totalHectares = (clone $query)->sum('farm_area_hectares');

        return [
            'title' => 'Booking Summary',
            'icon' => 'calendar',
            'rows' => [
                ['label' => 'Total Bookings', 'value' => $total],
                ['label' => 'Approved', 'value' => $approved],
                ['label' => 'Pending', 'value' => $pending],
                ['label' => 'Completed', 'value' => $completed],
                ['label' => 'Rejected', 'value' => $rejected],
                ['label' => 'Cancelled', 'value' => $cancelled],
                ['label' => 'Total Hectares', 'value' => round((float) $totalHectares, 1)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tractorUsage(Builder $query): array
    {
        $tractors = (clone $query)->get();
        $totalTractors = $tractors->count();
        $activeTractors = $tractors->where('is_active', true)->count();
        $totalHours = $tractors->sum('running_hours');
        $totalDistance = $tractors->sum('total_distance');
        $dueMaintenance = $tractors->filter(fn (Tractor $t) => $t->pmsStatus() === 'due')->count();

        return [
            'title' => 'Tractor Usage',
            'icon' => 'tractor',
            'rows' => [
                ['label' => 'Total Tractors', 'value' => $totalTractors],
                ['label' => 'Active', 'value' => $activeTractors],
                ['label' => 'Total Hours', 'value' => round((float) $totalHours, 1)],
                ['label' => 'Total Distance (km)', 'value' => round((float) $totalDistance, 1)],
                ['label' => 'Maintenance Due', 'value' => $dueMaintenance],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceSummary(Builder $query): array
    {
        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $inProgress = (clone $query)->where('status', 'in-progress')->count();
        $totalCost = (clone $query)->sum('cost');

        return [
            'title' => 'Maintenance Summary',
            'icon' => 'build',
            'rows' => [
                ['label' => 'Total Records', 'value' => $total],
                ['label' => 'Completed', 'value' => $completed],
                ['label' => 'In Progress', 'value' => $inProgress],
                ['label' => 'Pending', 'value' => $pending],
                ['label' => 'Total Cost', 'value' => round((float) $totalCost, 2)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function feedbackSummary(Builder $query): array
    {
        $total = (clone $query)->count();
        $avgRating = (clone $query)->avg('rating');
        $pending = (clone $query)->where('status', 'pending')->count();
        $reviewed = (clone $query)->where('status', 'reviewed')->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();

        return [
            'title' => 'Feedback Summary',
            'icon' => 'star',
            'rows' => [
                ['label' => 'Total Feedback', 'value' => $total],
                ['label' => 'Average Rating', 'value' => $avgRating ? round((float) $avgRating, 1) : 0],
                ['label' => 'Pending', 'value' => $pending],
                ['label' => 'Reviewed', 'value' => $reviewed],
                ['label' => 'Resolved', 'value' => $resolved],
            ],
        ];
    }
}
