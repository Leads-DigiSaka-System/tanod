<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketCommentAdded;
use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiTicketController extends Controller
{
    /**
     * List tickets scoped to the user's accessible tractors.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tractorIds = $this->accessibleTractorIds($user);

        $tickets = Ticket::with(['tractor:id,no_plate,brand,model', 'submitter:id,name'])
            ->where(function ($q) use ($user, $tractorIds) {
                $q->whereIn('tractor_id', $tractorIds)
                    ->orWhere('submitted_by', $user->id);
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate($request->per_page ?? 15);

        $tickets->getCollection()->transform(fn (Ticket $t) => $this->formatTicket($t));

        return response()->json($tickets);
    }

    /**
     * Show a single ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tractorIds = $this->accessibleTractorIds($user);
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

        return response()->json(['data' => $this->formatTicket($ticket, full: true)]);
    }

    /**
     * Create a new ticket with optional photo.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category' => ['nullable', Rule::in(['general', 'technical', 'billing', 'tractor', 'device', 'booking'])],
            'tractor_id' => 'nullable|exists:tractors,id',
            'photo' => 'nullable|image|max:5120',
        ]);

        if (! empty($data['tractor_id'])) {
            $tractorIds = $this->accessibleTractorIds($user);
            abort_unless(in_array((int) $data['tractor_id'], $tractorIds), 403, 'You do not have access to this tractor.');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('tickets', 'public');
        }

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'category' => $data['category'] ?? null,
            'tractor_id' => $data['tractor_id'] ?? null,
            'submitted_by' => $user->id,
            'status' => 'open',
            'photo_path' => $photoPath,
        ]);

        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name']);

        // Notify admins
        $adminIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'ticket_created',
                'title' => 'New Ticket',
                'body' => "{$user->name} submitted a new ticket: \"{$ticket->subject}\".",
                'data' => ['ticket_id' => $ticket->id],
            ]);
        }

        // Notify TPS users assigned to the tractor's group
        $tpsIds = [];
        if ($ticket->tractor_id) {
            $tpsIds = User::role('tps')
                ->where('is_active', true)
                ->whereHas('groups.tractors', fn ($q) => $q->where('tractors.id', $ticket->tractor_id))
                ->pluck('id')
                ->all();

            foreach ($tpsIds as $tpsId) {
                Notification::create([
                    'user_id' => $tpsId,
                    'type' => 'ticket_created',
                    'title' => 'Ticket on Your Tractor',
                    'body' => "A new ticket \"{$ticket->subject}\" was submitted for tractor {$ticket->tractor->no_plate}.",
                    'data' => ['ticket_id' => $ticket->id],
                ]);
            }
        }

        TicketCreated::dispatch($ticket, array_unique([...$adminIds, ...$tpsIds]));

        return response()->json(['data' => $this->formatTicket($ticket)], 201);
    }

    /**
     * Add a comment to a ticket.
     */
    public function addComment(Request $request, Ticket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tractorIds = $this->accessibleTractorIds($user);
        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        $request->validate([
            'body' => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'required_without:body|nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket-attachments', 'public');
        }

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $request->input('body', ''),
            'attachment_path' => $attachmentPath,
        ]);

        $comment->load(['user:id,name', 'ticket.assignees']);

        TicketCommentAdded::dispatch($comment);

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'attachment_url' => $this->storageUrl($comment->attachment_path),
                'user' => ['id' => $comment->user->id, 'name' => $comment->user->name],
                'created_at' => $comment->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Resolve a ticket with optional photo proof and notes.
     */
    public function resolve(Request $request, Ticket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tractorIds = $this->accessibleTractorIds($user);
        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        abort_unless(
            in_array($ticket->status, ['open', 'in_progress']),
            422,
            'This ticket cannot be resolved.'
        );

        $data = $request->validate([
            'resolution_notes' => 'nullable|string|max:5000',
            'resolution_photo' => 'nullable|image|max:5120',
        ]);

        $resolutionPhotoPath = null;
        if ($request->hasFile('resolution_photo')) {
            $resolutionPhotoPath = $request->file('resolution_photo')->store('tickets/resolutions', 'public');
        }

        $ticket->update([
            'status' => 'resolved',
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'resolution_photo_path' => $resolutionPhotoPath,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'assignees:id,name', 'resolver:id,name', 'comments.user:id,name']);

        return response()->json(['data' => $this->formatTicket($ticket, full: true)]);
    }

    // ─── Helpers ─────────────────────────────────

    /**
     * Get tractor IDs accessible by the given user (same logic as ApiTractorController).
     */
    private function accessibleTractorIds(\App\Models\User $user): array
    {
        $query = Tractor::query();

        if (! $user->hasAnyRole(['super-admin', 'sub-admin'])) {
            if ($user->hasRole('tps')) {
                $query->whereHas('groups.users', fn ($q) => $q->where('users.id', $user->id));
            } elseif ($user->hasRole('fca')) {
                $query->whereHas('distributions', fn ($q) => $q->where('distributed_to', $user->id)->where('status', 'distributed'));
            } elseif ($user->hasRole('farmer')) {
                $query->whereHas('distributions', fn ($q) => $q->where('distributed_to', $user->fca_id)->where('status', 'distributed'));
            } else {
                return [];
            }
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Build a storage URL that uses the incoming request's host.
     *
     * This ensures mobile clients (which hit a different host/port than
     * the configured APP_URL) receive reachable image URLs.
     */
    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return rtrim(request()->getSchemeAndHttpHost().request()->getBaseUrl(), '/').'/storage/'.$path;
    }

    /**
     * Transform a ticket model into the API response format.
     *
     * @return array<string, mixed>
     */
    private function formatTicket(Ticket $ticket, bool $full = false): array
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
        ];

        if ($full) {
            $data['resolution_notes'] = $ticket->resolution_notes;
            $data['resolution_photo_url'] = $this->storageUrl($ticket->resolution_photo_path);
            $data['resolved_by'] = $ticket->resolver ? [
                'id' => $ticket->resolver->id,
                'name' => $ticket->resolver->name,
            ] : null;
            $data['resolved_at'] = $ticket->resolved_at?->toIso8601String();
            $data['assignees'] = $ticket->assignees->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all();
            $data['comments'] = $ticket->comments->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'attachment_url' => $this->storageUrl($c->attachment_path),
                'user' => ['id' => $c->user->id, 'name' => $c->user->name],
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all();
        }

        return $data;
    }
}
