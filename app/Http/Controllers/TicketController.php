<?php

namespace App\Http\Controllers;

use App\Events\TicketCreated;
use App\Events\TicketStatusUpdated;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::with(['submittedBy', 'assignedTo', 'tractor'])
            ->when(!$user->hasAnyRole(['super-admin', 'sub-admin']), fn ($q) => $q->where('submitted_by', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->search, fn ($q, $s) => $q->where('subject', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status', 'priority']),
        ]);
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
        $ticket->load(['submittedBy', 'assignedTo', 'tractor', 'comments.user']);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $request->validate(['body' => 'required|string|max:5000']);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $ticket->update(['status' => $request->status]);

        if ($ticket->submitted_by !== $request->user()->id) {
            \App\Models\Notification::create([
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

    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate(['assigned_to' => 'required|exists:users,id']);

        $ticket->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Ticket assigned.');
    }
}
