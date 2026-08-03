<?php

namespace App\Http\Controllers;

use App\Events\TicketCommentAdded;
use App\Events\TicketCreated;
use App\Events\TicketStatusUpdated;
use App\Exports\TicketsExport;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\M360SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'subject', 'category', 'tractor_name', 'fca_name', 'description', 'service_charge', 'status', 'priority', 'created_at', 'reported_date'];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $cutoffDate = '2026-07-09';

        $tickets = Ticket::with(['submitter', 'assignees', 'tractor'])
            ->where('created_at', '>', $cutoffDate . ' 23:59:59')
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin']), fn ($q) => $q->where('submitted_by', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->search, fn ($q, $s) => $q->where('subject', 'like', "%{$s}%"))
            ->orderBy($sort, $direction)
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $oldTickets = Ticket::with(['submitter', 'assignees', 'tractor'])
            ->where('created_at', '<=', $cutoffDate . ' 23:59:59')
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin']), fn ($q) => $q->where('submitted_by', $user->id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'oldTickets' => $oldTickets,
            'filters' => $request->only(['search', 'status', 'priority', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ticket_ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No tickets selected for export.');
        }

        return Excel::download(
            new TicketsExport($ids),
            'tickets-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function create()
    {
        return Inertia::render('Tickets/Create', [
            'tractors' => \App\Models\Tractor::get(['id', 'no_plate', 'brand', 'model']),
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();
        $data['submitted_by'] = $request->user()->id;
        $data['status'] = 'open';

        // Resolve FCA name
        $user = $request->user();
        if (! isset($data['fca_name'])) {
            if ($user->hasRole('fca')) {
                $data['fca_name'] = $user->fcaProfile?->organization_name;
            } elseif ($user->hasRole('farmer') && $user->fca) {
                $data['fca_name'] = $user->fca->fcaProfile?->organization_name;
            }
        }

        $ticket = Ticket::create($data);

        $adminIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        TicketCreated::dispatch($ticket, $adminIds);

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket submitted successfully.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['submitter', 'assignees', 'tractor.images', 'resolver', 'comments.user', 'damagePhotos', 'tractorParts']);

        $tpsUsers = User::role('tps')
            ->where('is_active', true)
            ->get(['id', 'name', 'phone']);

        $assistanceRequests = Notification::where('type', 'assistance_requested')
            ->whereJsonContains('data->ticket_id', $ticket->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'created_at' => $n->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('Tickets/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'category' => $ticket->category,
                'fca_name' => $ticket->fca_name,
                'service_charge' => $ticket->service_charge,
                'down_payment' => $ticket->down_payment,
                'installments' => $ticket->installments,
                'photo_url' => $ticket->photo_path
                    ? asset('storage/'.$ticket->photo_path)
                    : null,
                'nameplate_photo_url' => $ticket->nameplate_photo_path
                    ? asset('storage/'.$ticket->nameplate_photo_path)
                    : null,
                'dashboard_photo_url' => $ticket->dashboard_photo_path
                    ? asset('storage/'.$ticket->dashboard_photo_path)
                    : null,
                'damage_photos' => $ticket->damagePhotos->map(fn ($dp) => [
                    'id' => $dp->id,
                    'photo_url' => asset('storage/'.$dp->photo_path),
                    'sort_order' => $dp->sort_order,
                ])->values()->all(),
                'tractor_parts' => $ticket->relationLoaded('tractorParts')
                    ? $ticket->tractorParts->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'amount' => $p->pivot->amount,
                        'quantity' => $p->pivot->quantity ?? 1,
                    ])->values()->all()
                    : [],
                'dr_photo_urls' => $ticket->dr_photo_paths
                    ? collect($ticket->dr_photo_paths)->map(fn ($p) => asset('storage/'.$p))->values()->all()
                    : [],
                'pms_checklist' => $ticket->pms_checklist,
                'resolution_notes' => $ticket->resolution_notes,
                'resolution_photo_url' => $ticket->resolution_photo_path
                    ? asset('storage/'.$ticket->resolution_photo_path)
                    : null,
                'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                'created_at' => $ticket->created_at?->toIso8601String(),
                'submitter' => $ticket->submitter ? [
                    'id' => $ticket->submitter->id,
                    'name' => $ticket->submitter->name,
                    'organization_name' => $ticket->submitter->organization_name,
                ] : null,
                'resolver' => $ticket->resolver ? [
                    'id' => $ticket->resolver->id,
                    'name' => $ticket->resolver->name,
                ] : null,
                'tractor' => $ticket->tractor ? [
                    'id' => $ticket->tractor->id,
                    'no_plate' => $ticket->tractor->no_plate,
                    'brand' => $ticket->tractor->brand,
                    'model' => $ticket->tractor->model,
                    'name' => $ticket->tractor->name,
                    'id_no' => $ticket->tractor->id_no,
                    'engine_no' => $ticket->tractor->engine_no,
                    'front_loader_sn' => $ticket->tractor->front_loader_sn,
                    'rotary_tiller_sn' => $ticket->tractor->rotary_tiller_sn,
                    'disc_plow_sn' => $ticket->tractor->disc_plow_sn,
                    'images' => $ticket->tractor->images->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/'.$img->path),
                        'type' => $img->type,
                    ])->values()->all(),
                ] : null,
                'assignees' => $ticket->assignees->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                ])->values()->all(),
                'comments' => $ticket->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'body' => $c->body,
                    'attachment_url' => $c->attachment_path ? asset('storage/'.$c->attachment_path) : null,
                    'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                    'created_at' => $c->created_at?->toIso8601String(),
                ])->all(),
            ],
            'tpsUsers' => $tpsUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()->all(),
            'assistanceRequests' => $assistanceRequests,
        ]);
    }

    public function addComment(Request $request, Ticket $ticket)
    {
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
            'user_id' => $request->user()->id,
            'body' => $request->input('body', ''),
            'attachment_path' => $attachmentPath,
        ]);

        $comment->load(['user', 'ticket.assignees']);

        $event = new TicketCommentAdded($comment);
        $event->socket = $socketId !== '' ? $socketId : null;

        event($event);

        return back()->with('success', 'Comment added.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $ticket->update(['status' => $request->status]);

        if ($ticket->submitted_by !== $request->user()->id) {
            Notification::create([
                'user_id' => $ticket->submitted_by,
                'type' => 'ticket_status_updated',
                'title' => 'Ticket Updated',
                'body' => "Your ticket \"{$ticket->subject}\" status changed to {$request->status}.",
                'data' => ['ticket_id' => $ticket->id],
            ]);

            TicketStatusUpdated::dispatch($ticket, $request->status, [$ticket->submitted_by]);
        }

        return back()->with('success', 'Ticket status updated.');
    }

    public function destroy(Ticket $ticket)
    {
        $user = request()->user();

        // Only super-admin, sub-admin, or the ticket submitter can delete
        if (! $user->hasAnyRole(['super-admin', 'sub-admin']) && $ticket->submitted_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $ticket->delete(); // soft delete

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        $newIds = collect($request->assignee_ids)->map(fn ($id) => (int) $id);
        $existingIds = $ticket->assignees()->pluck('users.id');
        $addedIds = $newIds->diff($existingIds);

        $ticket->assignees()->sync($newIds->all());

        // Also keep the first one in the legacy assigned_to column
        $ticket->update(['assigned_to' => $newIds->first()]);

        // Notify newly assigned TPS users
        if ($addedIds->isNotEmpty()) {
            $newAssignees = User::whereIn('id', $addedIds->all())->get();
            $sms = app(M360SmsService::class);

            foreach ($newAssignees as $tps) {
                Notification::create([
                    'user_id' => $tps->id,
                    'type' => 'ticket_assigned',
                    'title' => 'Ticket Assigned',
                    'body' => "You have been assigned to ticket \"{$ticket->subject}\".",
                    'data' => ['ticket_id' => $ticket->id],
                ]);

                TicketStatusUpdated::dispatch($ticket, 'assigned', [$tps->id]);

                if ($tps->phone) {
                    $sms->send($tps->phone, "TANOD: You have been assigned to ticket \"{$ticket->subject}\". Please check the app for details.");
                }
            }
        }

        return back()->with('success', 'Ticket assignees updated.');
    }
}
