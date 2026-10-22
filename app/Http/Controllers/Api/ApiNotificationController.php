<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))
            ->when(
                $request->boolean('assigned_chat_only') && $user->hasRole('tps'),
                fn ($q) => $this->scopeToAssignedTicketChats($q, $user)
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($notifications);
    }

    /**
     * Drop ticket comment notifications for tickets that are not assigned to
     * the user.
     *
     * A TPS who distributed a tractor can still read that ticket's chat, but
     * the chat badge must only count tickets that are actually assigned to
     * them.
     */
    private function scopeToAssignedTicketChats(Builder $query, User $user): Builder
    {
        $assignedTicketIds = Ticket::query()
            ->where(function ($ticketQuery) use ($user) {
                $ticketQuery->where('assigned_to', $user->id)
                    ->orWhereHas(
                        'assignees',
                        fn ($assigneeQuery) => $assigneeQuery->where('users.id', $user->id)
                    );
            })
            ->pluck('id');

        return $query->where(function ($notificationQuery) use ($assignedTicketIds) {
            $notificationQuery->where('type', '!=', 'ticket_comment')
                ->orWhereIn('data->ticket_id', $assignedTicketIds);
        });
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
