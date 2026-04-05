<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmerFeedback;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\User;
use App\Services\M360SmsService;
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
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status), fn (Builder $q) => $q->where('status', '!=', 'returned'))
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
        $groupTractorIds = Tractor::whereHas('groups.users', fn (Builder $q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $distributionTractorIds = TractorDistribution::where('tps_id', $user->id)
            ->where('status', 'distributed')
            ->pluck('tractor_id');

        return $groupTractorIds->merge($distributionTractorIds)->unique()->values()->all();
    }

    /**
     * Form data for creating a ticket (available tractors for dropdown).
     */
    public function ticketFormData(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $tractors = Tractor::whereIn('id', $tractorIds)
            ->select('id', 'no_plate', 'brand', 'model')
            ->get();

        return response()->json(['tractors' => $tractors]);
    }

    /**
     * Show a single ticket detail for TPS user.
     */
    public function ticketDetail(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        $ticket->load([
            'tractor:id,no_plate,brand,model',
            'submitter:id,name',
            'assignees:id,name',
            'resolver:id,name',
            'comments.user:id,name',
        ]);

        return response()->json(['data' => $this->formatTicket($ticket)]);
    }

    /**
     * Request assistance from admin for a ticket.
     * Notifies admins via in-app notification and SMS.
     */
    public function requestAssistance(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $admins = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->get(['id', 'name', 'phone']);

        $tractorLabel = $ticket->tractor
            ? $ticket->tractor->no_plate
            : 'N/A';

        // Create in-app notifications for all admins
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'assistance_requested',
                'title' => 'Assistance Requested',
                'body' => "{$user->name} needs assistance on ticket \"{$ticket->subject}\" (Tractor: {$tractorLabel}): {$data['message']}",
                'data' => ['ticket_id' => $ticket->id],
            ]);
        }

        // Broadcast to admin notification channels
        $adminIds = $admins->pluck('id')->all();
        \App\Events\TicketStatusUpdated::dispatch($ticket, 'assistance_requested', $adminIds);

        // Send SMS to admins with phone numbers
        $smsService = app(M360SmsService::class);
        $smsMessage = "TANOD Alert: TPS {$user->name} requests assistance for ticket \"{$ticket->subject}\" (Tractor: {$tractorLabel}). Message: {$data['message']}";

        foreach ($admins as $admin) {
            if (! empty($admin->phone)) {
                $smsService->send($admin->phone, $smsMessage);
            }
        }

        return response()->json(['message' => 'Assistance request sent to admins.']);
    }

    /**
     * Transform a ticket model into the API response format.
     *
     * @return array<string, mixed>
     */
    private function formatTicket(Ticket $ticket): array
    {
        $data = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'photo_url' => $this->storageUrl($ticket->photo_path),
            'tractor' => $ticket->tractor ? [
                'id' => $ticket->tractor->id,
                'no_plate' => $ticket->tractor->no_plate,
                'brand' => $ticket->tractor->brand,
                'model' => $ticket->tractor->model,
            ] : null,
            'submitted_by' => $ticket->submitter ? [
                'id' => $ticket->submitter->id,
                'name' => $ticket->submitter->name,
            ] : null,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'resolution_notes' => $ticket->resolution_notes,
            'resolution_photo_url' => $this->storageUrl($ticket->resolution_photo_path),
            'resolved_by' => $ticket->resolver ? [
                'id' => $ticket->resolver->id,
                'name' => $ticket->resolver->name,
            ] : null,
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'assignees' => $ticket->assignees?->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all() ?? [],
            'comments' => $ticket->comments?->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'attachment_url' => $this->storageUrl($c->attachment_path),
                'user' => ['id' => $c->user->id, 'name' => $c->user->name],
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all() ?? [],
        ];

        return $data;
    }

    /**
     * Build a storage URL that uses the incoming request's host.
     */
    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return rtrim(request()->getSchemeAndHttpHost().request()->getBaseUrl(), '/').'/storage/'.$path;
    }

    /**
     * Form data for creating a distribution (available tractors + FCA users).
     */
    public function distributionFormData(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        // Tractors already actively distributed
        $distributedTractorIds = TractorDistribution::whereIn('tractor_id', $tractorIds)
            ->where('status', 'distributed')
            ->pluck('tractor_id')
            ->all();

        $tractors = Tractor::whereIn('id', $tractorIds)
            ->select('id', 'no_plate', 'brand', 'model')
            ->get()
            ->map(fn (Tractor $t) => [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'is_distributed' => in_array($t->id, $distributedTractorIds),
            ]);

        $fcaUsers = User::role('fca')
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'tractors' => $tractors,
            'fca_users' => $fcaUsers,
        ]);
    }

    /**
     * Store a new tractor distribution from the TPS mobile app.
     */
    public function storeDistribution(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->tractorIdsForTps($user);

        $validated = $request->validate([
            'tractor_id' => ['required', 'integer', 'exists:tractors,id'],
            'distributed_to' => ['required', 'integer', 'exists:users,id'],
            'area' => ['required', 'string', 'max:255'],
            'distribution_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Ensure the tractor belongs to this TPS user's group
        if (! in_array((int) $validated['tractor_id'], $tractorIds)) {
            return response()->json(['message' => 'This tractor is not assigned to your group.'], 403);
        }

        // Ensure the tractor is not already actively distributed
        $alreadyDistributed = TractorDistribution::where('tractor_id', $validated['tractor_id'])
            ->where('status', 'distributed')
            ->exists();

        if ($alreadyDistributed) {
            return response()->json(['message' => 'This tractor is already distributed.'], 422);
        }

        $proofPath = null;
        if ($request->hasFile('proof_photo')) {
            $proofPath = $request->file('proof_photo')->store('distributions/proofs', 'public');
        }

        $distribution = TractorDistribution::create([
            'tractor_id' => $validated['tractor_id'],
            'tractor_ids' => [$validated['tractor_id']],
            'distributed_to' => $validated['distributed_to'],
            'distributed_by' => $user->id,
            'tps_id' => $user->id,
            'area' => $validated['area'],
            'distribution_date' => $validated['distribution_date'],
            'notes' => $validated['notes'] ?? null,
            'proof_photo' => $proofPath,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => 'distributed',
        ]);

        $distribution->load(['tractor:id,no_plate,brand,model', 'distributedToUser:id,name,email']);

        return response()->json([
            'message' => 'Tractor distributed successfully.',
            'distribution' => $distribution,
        ], 201);
    }
}
