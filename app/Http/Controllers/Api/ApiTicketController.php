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
use Illuminate\Database\Eloquent\Builder;
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
        $forChat = $request->boolean('for_chat');

        $tickets = Ticket::query()
            ->with([
                'tractor:id,no_plate,brand,model',
                'submitter:id,name',
                'latestComment.user:id,name',
                'damagePhotos',
            ])
            ->withMax('comments', 'created_at')
            ->when(
                $forChat && $user->hasRole('fca'),
                fn (Builder $query) => $query->where('submitted_by', $user->id),
                function (Builder $query) use ($user, $tractorIds) {
                    $query->where(function (Builder $ticketQuery) use ($user, $tractorIds) {
                        $ticketQuery->whereIn('tractor_id', $tractorIds)
                            ->orWhere('submitted_by', $user->id);
                    });
                }
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->orderByRaw('coalesce(comments_max_created_at, tickets.created_at) desc')
            ->orderByDesc('tickets.id')
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
            'damagePhotos',
        ]);

        return response()->json(['data' => $this->formatTicket($ticket, full: true)]);
    }

    /**
     * Create a new ticket with a required proof photo.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category' => 'nullable|string|max:100',
            'tractor_id' => 'nullable|exists:tractors,id',
            'nameplate_photo' => 'required|image|max:10240',
            'dashboard_photo' => 'required|image|max:10240',
            'damage_photos' => 'required|array|min:1|max:3',
            'damage_photos.*' => 'image|max:10240',
        ]);

        $data['priority'] = $data['priority'] ?? 'medium';

        if (! empty($data['tractor_id'])) {
            $tractorIds = $this->accessibleTractorIds($user);
            abort_unless(in_array((int) $data['tractor_id'], $tractorIds), 403, 'You do not have access to this tractor.');
        }

        $nameplatePath = $request->file('nameplate_photo')->store('tickets/nameplates', 'public');
        $dashboardPath = $request->file('dashboard_photo')->store('tickets/dashboards', 'public');

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'category' => $data['category'] ?? null,
            'tractor_id' => $data['tractor_id'] ?? null,
            'submitted_by' => $user->id,
            'status' => 'open',
            'nameplate_photo_path' => $nameplatePath,
            'dashboard_photo_path' => $dashboardPath,
        ]);

        foreach ($request->file('damage_photos') as $i => $file) {
            $ticket->damagePhotos()->create([
                'photo_path' => $file->store('tickets/damages', 'public'),
                'sort_order' => $i,
            ]);
        }

        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'damagePhotos']);

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
            $tpsIds = User::tpsIdsForTractor($ticket->tractor_id);

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
            'socket_id' => 'nullable|string|max:100',
            'attachment' => 'required_without:body|nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ]);

        $socketId = $request->header('X-Socket-ID') ?: $request->string('socket_id')->toString();

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

        $event = new TicketCommentAdded($comment);
        $event->socket = $socketId !== '' ? $socketId : null;

        event($event);

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

    /**
     * Close a resolved ticket.
     */
    public function close(Request $request, Ticket $ticket)
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
            in_array($ticket->status, ['open', 'in_progress', 'resolved']),
            422,
            'This ticket cannot be closed.'
        );

        $ticket->update(['status' => 'closed']);

        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'assignees:id,name', 'resolver:id,name', 'comments.user:id,name']);

        // Notify relevant users
        $recipientIds = collect([$ticket->submitted_by])
            ->merge(User::role(['super-admin', 'sub-admin'])->where('is_active', true)->pluck('id'))
            ->unique()
            ->filter(fn ($id) => $id !== $user->id)
            ->values()
            ->all();

        foreach ($recipientIds as $recipientId) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'ticket_closed',
                'title' => 'Ticket Closed',
                'body' => "{$user->name} closed ticket: \"{$ticket->subject}\".",
                'data' => ['ticket_id' => $ticket->id],
            ]);
        }

        \App\Events\TicketStatusUpdated::dispatch($ticket, 'closed', $recipientIds);

        return response()->json(['data' => $this->formatTicket($ticket, full: true)]);
    }

    // ─── Helpers ─────────────────────────────────

    /**
     * Get tractor IDs accessible by the given user (same logic as ApiTractorController).
     */
    private function accessibleTractorIds(\App\Models\User $user): array
    {
        return $user->accessibleTractorIds();
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

        return request()->getSchemeAndHttpHost().'/storage/'.$path;
    }

    /**
     * Transform a ticket model into the API response format.
     *
     * @return array<string, mixed>
     */
    private function formatTicket(Ticket $ticket, bool $full = false): array
    {
        $latestComment = $ticket->latestComment;
        $lastActivityAt = $latestComment?->created_at ?? $ticket->created_at;

        $data = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'photo_url' => $this->storageUrl($ticket->photo_path),
            'nameplate_photo_url' => $this->storageUrl($ticket->nameplate_photo_path),
            'dashboard_photo_url' => $this->storageUrl($ticket->dashboard_photo_path),
            'damage_photos' => $ticket->relationLoaded('damagePhotos')
                ? $ticket->damagePhotos->map(fn ($dp) => [
                    'id' => $dp->id,
                    'photo_url' => $this->storageUrl($dp->photo_path),
                    'sort_order' => $dp->sort_order,
                ])->values()->all()
                : [],
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
            'last_activity_at' => $lastActivityAt?->toIso8601String(),
            'last_comment' => $latestComment ? [
                'id' => $latestComment->id,
                'ticket_id' => $latestComment->ticket_id,
                'body' => $latestComment->body,
                'attachment_url' => $this->storageUrl($latestComment->attachment_path),
                'user' => $latestComment->user ? [
                    'id' => $latestComment->user->id,
                    'name' => $latestComment->user->name,
                ] : null,
                'created_at' => $latestComment->created_at?->toIso8601String(),
            ] : null,
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
