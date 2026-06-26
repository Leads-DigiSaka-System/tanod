<?php

namespace App\Http\Controllers;

use App\Models\FarmerFeedback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $feedback = FarmerFeedback::with(['tractor', 'booking', 'submitter', 'reviewer'])
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin']), function ($q) use ($user) {
                // FCA sees feedback from farmers in their groups
                if ($user->hasRole('fca')) {
                    $groupUserIds = $user->groups()
                        ->with('users')
                        ->get()
                        ->pluck('users')
                        ->flatten()
                        ->pluck('id')
                        ->unique();
                    $q->whereIn('submitted_by', $groupUserIds->merge([$user->id]));
                } else {
                    // Farmer / operator sees only their own
                    $q->where('submitted_by', $user->id);
                }
            })
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q2) use ($s) {
                    $q2->where('feedback', 'like', "%{$s}%")
                        ->orWhere('conclusion', 'like', "%{$s}%")
                        ->orWhereHas('submitter', fn ($q3) => $q3->where('name', 'like', "%{$s}%"));
                });
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->rating, fn ($q, $r) => $q->where('rating', $r))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Summary stats
        $baseQuery = FarmerFeedback::query();
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'reviewed' => (clone $baseQuery)->where('status', 'reviewed')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'avg_rating' => round((clone $baseQuery)->whereNotNull('rating')->avg('rating'), 1) ?: 0,
        ];

        return Inertia::render('Feedback/Index', [
            'feedback' => $feedback,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'category', 'rating']),
        ]);
    }

    public function show(FarmerFeedback $feedback)
    {
        $feedback->load(['tractor', 'booking', 'submitter', 'reviewer']);

        return Inertia::render('Feedback/Show', [
            'feedback' => $feedback,
        ]);
    }

    public function review(Request $request, FarmerFeedback $feedback)
    {
        $request->validate([
            'status' => 'required|in:reviewed,resolved,dismissed',
            'admin_response' => 'nullable|string|max:2000',
            'conclusion' => 'nullable|string|max:2000',
        ]);

        $feedback->update([
            'status' => $request->status,
            'admin_response' => $request->admin_response,
            'conclusion' => $request->conclusion ?? $feedback->conclusion,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Feedback updated successfully.');
    }
}
