<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->when($request->has('unread'), fn ($q) => $q->where('is_read', false))
            ->when($request->filled('type'), fn ($q) => $q->where('type', 'like', $request->type.'%'));

        $notifications = $query->latest()
            ->paginate(20)
            ->withQueryString();

        $typeCounts = Notification::where('user_id', $request->user()->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['unread', 'type']),
            'typeCounts' => $typeCounts,
        ]);
    }

    /**
     * Return the latest notifications as JSON for the bell dropdown.
     */
    public function recent(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->limit(10)
            ->get();

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }
}
