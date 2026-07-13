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
     * - TSR: feedbacks on assigned tractors or the full fleet when enabled
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
            $query->where(function (Builder $q) use ($user) {
                $q->where('submitted_by', $user->id)
                  ->orWhereHas('tractor.distributions', fn (Builder $dq) => $dq->where('distributed_to', $user->id)
                      ->where('status', 'distributed'));
            });
        } elseif ($user->hasRole('tsr')) {
            $query->whereIn('tractor_id', $user->accessibleTractorIds());
        } else {
            $query->whereRaw('0 = 1');
        }

        return response()->json(
            $query->paginate($request->per_page ?? 20)
        );
    }

    /**
     * Store feedback (farmer or FCA).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['farmer', 'fca'])) {
            return response()->json(['message' => 'Only farmers and FCAs can submit feedback.'], 403);
        }

        $validated = $request->validate([
            'tractor_id' => ['required', 'integer', 'exists:tractors,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        // Verify the user has access to this tractor
        if ($user->hasRole('farmer')) {
            $hasAccess = Tractor::where('id', $validated['tractor_id'])
                ->whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->fca_id)
                    ->where('status', 'distributed'))
                ->exists();
        } else {
            // FCA: check if tractor is distributed to them
            $hasAccess = Tractor::where('id', $validated['tractor_id'])
                ->whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                    ->where('status', 'distributed'))
                ->exists();
        }

        if (! $hasAccess) {
            return response()->json(['message' => 'You do not have access to this tractor.'], 403);
        }

        $validated['submitted_by'] = $user->id;
        $validated['status'] = 'pending';

        $feedback = FarmerFeedback::create($validated);
        $feedback->load(['tractor:id,no_plate,brand,model,device_id', 'submitter:id,name']);

        // Notify recipients
        $this->notifyRecipients($feedback, $user);

        return response()->json(['data' => $feedback, 'message' => 'Feedback submitted.'], 201);
    }

    /**
     * List tractors the user can give feedback on.
     */
    public function tractors(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['farmer', 'fca'])) {
            return response()->json(['message' => 'Only farmers and FCAs can access this.'], 403);
        }

        $tractorQuery = Tractor::where('is_active', true)
            ->whereHas('device', fn ($q) => $q->notStale());

        if ($user->hasRole('farmer') && $user->fca_id) {
            $tractorQuery->whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->fca_id)
                ->where('status', 'distributed'));
        } elseif ($user->hasRole('fca')) {
            $tractorQuery->whereHas('distributions', fn (Builder $q) => $q->where('distributed_to', $user->id)
                ->where('status', 'distributed'));
        } else {
            return response()->json(['data' => []]);
        }

        $tractors = $tractorQuery->get(['id', 'no_plate', 'brand', 'model']);

        return response()->json(['data' => $tractors]);
    }

    /**
     * Notify FCA and TSR users who have access to the tractor.
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

        $tsrIds = User::tsrIdsForTractor($tractor->id);

        $recipientIds = collect([...$fcaIds, ...$tsrIds])
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
