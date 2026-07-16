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
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id
            || $ticket->assigned_to === $user->id
            || $ticket->assignees()->where('user_id', $user->id)->exists(),
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
            'tractorParts',
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
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
            'nameplate_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
            'dashboard_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
            'damage_photos' => 'nullable|array|min:1|max:10',
            'damage_photos.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
            'pms_checklist' => 'nullable|array',
            'pms_checklist.*.name' => 'required_with:pms_checklist|string',
            'pms_checklist.*.done' => 'required_with:pms_checklist|boolean',
            'pms_checklist.*.notes' => 'nullable|string|max:500',
            'auto_resolve' => 'nullable|boolean',
            'action_taken' => 'nullable|string|in:Self PMS,Third Party,Need Technician Help,Self Repair,Third Party Repair',
            'reported_date' => 'nullable|date',
        ]);

        $data['priority'] = $data['priority'] ?? 'medium';

        if (! empty($data['tractor_id'])) {
            $tractorIds = $this->accessibleTractorIds($user);
            abort_unless(in_array((int) $data['tractor_id'], $tractorIds), 403, 'You do not have access to this tractor.');
        }

        // Handle photo uploads — supports both mobile (single 'photo') and web (separate fields)
        $photoPath = null;
        $nameplatePath = null;
        $dashboardPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('tickets/photos', 'public');
        }

        if ($request->hasFile('nameplate_photo')) {
            $nameplatePath = $request->file('nameplate_photo')->store('tickets/nameplates', 'public');
        }

        if ($request->hasFile('dashboard_photo')) {
            $dashboardPath = $request->file('dashboard_photo')->store('tickets/dashboards', 'public');
        }

        $fcaName = null;
        if ($user->hasRole('fca')) {
            $fcaName = $user->fcaProfile?->organization_name;
        } elseif ($user->hasRole('farmer') && $user->fca) {
            $fcaName = $user->fca->fcaProfile?->organization_name;
        }

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'category' => $data['category'] ?? null,
            'tractor_id' => $data['tractor_id'] ?? null,
            'submitted_by' => $user->id,
            'fca_name' => $fcaName,
            'reported_date' => $data['reported_date'] ?? null,
            'status' => ! empty($data['auto_resolve']) ? 'resolved' : 'open',
            'photo_path' => $photoPath,
            'nameplate_photo_path' => $nameplatePath,
            'dashboard_photo_path' => $dashboardPath,
            'pms_checklist' => $data['pms_checklist'] ?? null,
            'resolution_notes' => ! empty($data['auto_resolve'])
                ? ('Completed via '.($data['action_taken'] ?? 'self-service').'.')
                : null,
            'resolved_by' => ! empty($data['auto_resolve']) ? $user->id : null,
            'resolved_at' => ! empty($data['auto_resolve']) ? now() : null,
        ]);

        if ($request->hasFile('damage_photos')) {
            foreach ($request->file('damage_photos') as $i => $file) {
                $ticket->damagePhotos()->create([
                    'photo_path' => $file->store('tickets/damages', 'public'),
                    'sort_order' => $i,
                ]);
            }
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

        // Notify TSR users assigned to the tractor's group
        $tsrIds = [];
        if ($ticket->tractor_id) {
            $tsrIds = User::tsrIdsForTractor($ticket->tractor_id);

            foreach ($tsrIds as $tpsId) {
                Notification::create([
                    'user_id' => $tpsId,
                    'type' => 'ticket_created',
                    'title' => 'Ticket on Your Tractor',
                    'body' => "A new ticket \"{$ticket->subject}\" was submitted for tractor {$ticket->tractor->no_plate}.",
                    'data' => ['ticket_id' => $ticket->id],
                ]);
            }
        }

        try {
            TicketCreated::dispatch($ticket, array_unique([...$adminIds, ...$tsrIds]));
        } catch (\Throwable $e) {
            // Broadcast failure (e.g. SSL cert issue) should not block ticket creation
            \Illuminate\Support\Facades\Log::warning('Ticket broadcast failed: '.$e->getMessage());
        }

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
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id
            || $ticket->assigned_to === $user->id
            || $ticket->assignees()->where('user_id', $user->id)->exists(),
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
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id
            || $ticket->assigned_to === $user->id
            || $ticket->assignees()->where('user_id', $user->id)->exists(),
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
            'service_charge' => 'nullable|numeric|min:0|max:99999999.99',
            'down_payment' => 'nullable|numeric|min:0|max:99999999.99',
            'installments' => 'nullable|integer|min:1|max:12',
            'partial' => 'nullable|boolean',
            'parts' => 'nullable|array',
            'parts.*.id' => 'nullable|integer|exists:tractor_parts,id',
            'parts.*.name' => 'nullable|string|max:255',
            'parts.*.amount' => 'required_with:parts|numeric|min:0|max:99999999.99',
            'parts.*.quantity' => 'nullable|integer|min:1|max:999',
            'dr_photos' => 'nullable|array|max:3',
            'dr_photos.*' => 'image|max:5120',
            // Report-specific fields
            'customer_address' => 'nullable|string|max:2000',
            'contact_no' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'machine_hours' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'warranty_type' => 'nullable|string|max:100',
            'service_performed' => 'nullable|array',
            'service_performed.*' => 'string|max:50',
            'repair_start_date' => 'nullable|date',
            'repair_end_date' => 'nullable|date|after_or_equal:repair_start_date',
        ]);

        $resolutionPhotoPath = null;
        if ($request->hasFile('resolution_photo')) {
            $resolutionPhotoPath = $request->file('resolution_photo')->store('tickets/resolutions', 'public');
        }

        // Merge new DR photos with existing ones
        $existingDrPaths = $ticket->dr_photo_paths ?? [];
        if ($request->hasFile('dr_photos')) {
            foreach ($request->file('dr_photos') as $photo) {
                $existingDrPaths[] = $photo->store('tickets/dr', 'public');
            }
        }
        // Handle removed photos - if a list of kept URLs is provided
        if ($request->has('keep_dr_photos')) {
            $keepUrls = $request->input('keep_dr_photos', []);
            $existingDrPaths = array_filter($existingDrPaths, function ($path) use ($keepUrls) {
                return in_array(asset('storage/'.$path), $keepUrls);
            });
        }

        $updateData = [
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'service_charge' => $data['service_charge'] ?? null,
            'down_payment' => $data['down_payment'] ?? null,
            'installments' => $data['installments'] ?? null,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'dr_photo_paths' => ! empty($existingDrPaths) ? array_values($existingDrPaths) : null,
        ];

        // Only update resolution_photo_path when a new file is actually uploaded
        if ($request->hasFile('resolution_photo')) {
            $updateData['resolution_photo_path'] = $resolutionPhotoPath;
        }

        // Partial resolve: don't change status, just update info
        if (! ($data['partial'] ?? false)) {
            $updateData['status'] = 'resolved';
        }

        $ticket->update($updateData);

        // Sync tractor parts
        $partsData = [];
        if ($request->has('parts')) {
            foreach ($data['parts'] as $part) {
                if (! empty($part['name']) && empty($part['id'])) {
                    // Create new part on-the-fly
                    $newPart = TractorPart::create(['name' => $part['name'], 'amount' => $part['amount']]);
                    $part['id'] = $newPart->id;
                }
                $partsData[$part['id']] = ['amount' => $part['amount'], 'quantity' => $part['quantity'] ?? 1];
            }
            $ticket->tractorParts()->sync($partsData);
        }

        // ─── Auto-generate Ticket Report ───
        if (! ($data['partial'] ?? false)) {
            $this->generateTicketReport($ticket, $user, $data, $resolutionPhotoPath, $existingDrPaths, $partsData);
        }

        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'assignees:id,name', 'resolver:id,name', 'comments.user:id,name', 'tractorParts']);

        return response()->json(['data' => $this->formatTicket($ticket, full: true)]);
    }

    /**
     * Auto-generate a ticket report when a ticket is resolved.
     */
    private function generateTicketReport(Ticket $ticket, $user, array $data, ?string $resolutionPhotoPath, array $existingDrPaths, array $partsData): void
    {
        // Parse resolution notes into findings/job_done/recommendation/remarks
        $findings = null;
        $jobDone = null;
        $recommendation = null;
        $remarks = null;

        if ($data['resolution_notes']) {
            $lines = explode("\n", $data['resolution_notes']);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'Findings: ')) {
                    $findings = substr($line, strlen('Findings: '));
                } elseif (str_starts_with($line, 'Job Done: ')) {
                    $jobDone = substr($line, strlen('Job Done: '));
                } elseif (str_starts_with($line, 'Recommendation: ')) {
                    $recommendation = substr($line, strlen('Recommendation: '));
                } elseif (str_starts_with($line, 'Remarks: ')) {
                    $remarks = substr($line, strlen('Remarks: '));
                }
            }
        }

        // Build parts details
        $partsDetails = [];
        $partsTotal = 0;
        if (! empty($partsData)) {
            $partModels = \App\Models\TractorPart::whereIn('id', array_keys($partsData))->get()->keyBy('id');
            foreach ($partsData as $partId => $partInfo) {
                $partModel = $partModels->get($partId);
                $qty = (int) ($partInfo['quantity'] ?? 1);
                $amount = (float) ($partInfo['amount'] ?? 0);
                $lineTotal = $qty * $amount;
                $partsTotal += $lineTotal;
                $partsDetails[] = [
                    'id' => $partId,
                    'name' => $partModel?->name ?? 'Part #'.$partId,
                    'quantity' => $qty,
                    'amount' => $amount,
                    'line_total' => $lineTotal,
                ];
            }
        }

        // Build DR photo URLs
        $drPhotoUrls = [];
        foreach ($existingDrPaths as $path) {
            $drPhotoUrls[] = asset('storage/'.$path);
        }

        // Create the report
        $ticket->load(['tractor:id,no_plate,brand,model', 'submitter:id,name']);

        // Auto-detect serial number from FCA tractor details based on subject
        $serialNumber = $data['serial_number'] ?? null;
        if (! $serialNumber) {
            $serialNumber = $this->detectSerialNumber($ticket);
        }

        \App\Models\TicketReport::create([
            'ticket_id' => $ticket->id,
            'tps_id' => $user->id,
            'ticket_no' => 'TKT-'.$ticket->id,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'fca_name' => $ticket->fca_name,
            'submitted_by_name' => $ticket->submitter?->name,
            'customer_address' => $data['customer_address'] ?? null,
            'contact_no' => $data['contact_no'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'tractor_plate' => $ticket->tractor?->no_plate,
            'tractor_brand' => $ticket->tractor?->brand,
            'tractor_model' => $ticket->tractor?->model,
            'machine_hours' => $data['machine_hours'] ?? null,
            'serial_number' => $serialNumber,
            'warranty_type' => $data['warranty_type'] ?? null,
            'service_performed' => $data['service_performed'] ?? null,
            'repair_start_date' => $data['repair_start_date'] ?? null,
            'repair_end_date' => $data['repair_end_date'] ?? null,
            'findings' => $findings,
            'job_done' => $jobDone,
            'recommendation' => $recommendation,
            'remarks' => $remarks,
            'service_charge' => $data['service_charge'] ?? null,
            'down_payment' => $data['down_payment'] ?? null,
            'installments' => $data['installments'] ?? null,
            'parts_total' => $partsTotal > 0 ? $partsTotal : null,
            'parts_details' => ! empty($partsDetails) ? $partsDetails : null,
            'resolution_photo_url' => $resolutionPhotoPath ? asset('storage/'.$resolutionPhotoPath) : null,
            'dr_photo_urls' => ! empty($drPhotoUrls) ? $drPhotoUrls : null,
            'status' => 'draft',
            'work_status' => 'Completed',
            'generated_at' => now(),
        ]);
    }

    /**
     * Auto-detect serial number from FCA tractor details based on subject.
     */
    private function detectSerialNumber(Ticket $ticket): ?string
    {
        if (! $ticket->tractor_id) {
            return null;
        }

        $tractor = \App\Models\Tractor::find($ticket->tractor_id);
        if (! $tractor) {
            return null;
        }

        $subject = strtolower($ticket->subject ?? '');

        if (str_contains($subject, 'rovator') || str_contains($subject, 'rotavator')) {
            return $tractor->rotary_tiller_sn;
        }

        if (str_contains($subject, 'disc') || str_contains($subject, 'disk plow')) {
            return $tractor->disc_plow_sn;
        }

        if (str_contains($subject, 'loader')) {
            return $tractor->front_loader_sn;
        }

        // Default: return main tractor serial number (id_no)
        return $tractor->id_no;
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
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id
            || $ticket->assigned_to === $user->id
            || $ticket->assignees()->where('user_id', $user->id)->exists(),
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

        try {
            \App\Events\TicketStatusUpdated::dispatch($ticket, 'closed', $recipientIds);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Ticket close broadcast failed: '.$e->getMessage());
        }

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

        return asset('storage/'.$path);
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
            'is_partial' => $ticket->status === 'open' && ($ticket->resolution_notes !== null || $ticket->service_charge !== null || $ticket->down_payment !== null),
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
            'pms_checklist' => $ticket->pms_checklist,
            'service_charge' => $ticket->service_charge,
            'action_taken' => $ticket->resolution_notes
                ? (str_contains($ticket->resolution_notes, 'Third Party Repair') ? 'Third Party Repair'
                    : (str_contains($ticket->resolution_notes, 'Third Party') ? 'Third Party'
                        : (str_contains($ticket->resolution_notes, 'Self Repair') ? 'Self Repair'
                            : (str_contains($ticket->resolution_notes, 'Need Technician') ? 'Need Technician Help'
                                : 'Self PMS'))))
                : null,
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
            'fca_name' => $ticket->fca_name,
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
            $data['down_payment'] = $ticket->down_payment;
            $data['installments'] = $ticket->installments;
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
            $data['tractor_parts'] = $ticket->relationLoaded('tractorParts')
                ? $ticket->tractorParts->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'amount' => (float) ($p->pivot->amount ?? 0),
                    'quantity' => (int) ($p->pivot->quantity ?? 1),
                ])->values()->all()
                : [];
            $data['dr_photo_urls'] = $ticket->dr_photo_paths
                ? collect($ticket->dr_photo_paths)->map(fn ($p) => asset('storage/'.$p))->values()->all()
                : [];
        }

        return $data;
    }
}
