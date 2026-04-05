<?php

namespace App\Http\Controllers\Api;

use App\Events\FeedbackCreated;
use App\Http\Controllers\Controller;
use App\Models\FarmerFeedback;
use App\Models\Tractor;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiFeedbackController extends Controller
{
    /**
     * List feedbacks visible to the user.
     * - farmer: own feedbacks
     * - fca: feedbacks on tractors distributed to them
     * - tps: feedbacks on tractors in their groups
     * - admin: all
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = FarmerFeedback::with([
            'tractor:id,no_plate,brand,model,device_id',
            'submitter:id,name',
        ])->latest();

        if ($user->hasAnyRole(['super-admin', 'sub-admin'])) {
            // admin sees all
        } elseif ($user->hasRole('farmer')) {
            $query->where('submitted_by', $user->id);
        } elseif ($user->hasRole('fca')) {
            $query->whereHas('tractor.distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                ->where('status', 'distributed'));
        } elseif ($user->hasRole('tps')) {
            $tractorIds = Tractor::whereHas('groups.users', fn (Builder $q) => $q->where('users.id', $user->id))
                ->pluck('id')
                ->merge(
                    \App\Models\TractorDistribution::where('tps_id', $user->id)
                        ->where('status', 'distributed')
                        ->pluck('tractor_id')
                )
                ->unique()
                ->values()
                ->all();
            $query->whereIn('tractor_id', $tractorIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        return response()->json(
            $query->paginate($request->per_page ?? 20)
        );
    }

    /**
     * Store feedback (farmer only).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('farmer')) {
            return response()->json(['message' => 'Only farmers can submit feedback.'], 403);
        }

        $validated = $request->validate([
            'tractor_id' => ['required', 'integer', 'exists:tractors,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        // Verify the farmer has access to this tractor (via their FCA's distributions)
        $hasAccess = Tractor::where('id', $validated['tractor_id'])
            ->whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->fca_id)
                ->where('status', 'distributed'))
            ->exists();

        if (! $hasAccess) {
            return response()->json(['message' => 'You do not have access to this tractor.'], 403);
        }

        $validated['submitted_by'] = $user->id;
        $validated['status'] = 'pending';

        $feedback = FarmerFeedback::create($validated);
        $feedback->load(['tractor:id,no_plate,brand,model,device_id', 'submitter:id,name']);

        // Notify FCA and TPS who have access to this tractor
        $this->notifyRecipients($feedback, $user);

        return response()->json(['data' => $feedback, 'message' => 'Feedback submitted.'], 201);
    }

    /**
     * List tractors the farmer can give feedback on.
     */
    public function tractors(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('farmer')) {
            return response()->json(['message' => 'Only farmers can access this.'], 403);
        }

        $tractors = Tractor::whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->fca_id)
            ->where('status', 'distributed'))
            ->where('is_active', true)
            ->get(['id', 'no_plate', 'brand', 'model']);

        return response()->json(['data' => $tractors]);
    }

    /**
     * Notify FCA and TPS users who have access to the tractor.
     */
    private function notifyRecipients(FarmerFeedback $feedback, User $farmer): void
    {
        $tractor = $feedback->tractor;

        // FCA users who have this tractor distributed to them
        $fcaIds = $tractor->distributions()
            ->where('status', 'distributed')
            ->pluck('distributed_to')
            ->unique()
            ->all();

        // TPS users in groups linked to this tractor
        $tpsIds = User::whereHas('groups', fn (Builder $q) => $q->whereHas('tractors', fn (Builder $q2) => $q2->where('tractors.id', $tractor->id)))
            ->role('tps')
            ->pluck('id')
            ->all();

        $recipientIds = collect([...$fcaIds, ...$tpsIds])
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        // Broadcast via WebSocket
        FeedbackCreated::dispatch($feedback, $recipientIds);

        // Send FCM push notification
        $recipients = User::whereIn('id', $recipientIds)->get();
        $tractorLabel = trim("{$tractor->brand} {$tractor->model} ({$tractor->no_plate})");

        app(FcmService::class)->sendToUsers(
            $recipients,
            'New Feedback Received',
            "{$farmer->name} rated {$tractorLabel} — {$feedback->rating}/5 stars",
            [
                'type' => 'feedback_created',
                'feedback_id' => (string) $feedback->id,
                'tractor_id' => (string) $tractor->id,
            ],
        );
    }
}
