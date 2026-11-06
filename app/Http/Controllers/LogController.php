<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('performer')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('model_type', 'like', "%{$s}%")
                    ->orWhere('action', 'like', "%{$s}%")
                    ->orWhereHas('performer', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
            }))
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action']),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function cleanup(Request $request)
    {
        $deleted = ActivityLog::where('created_at', '<', now()->subDays(3))->delete();

        ActivityLogger::log('ActivityLog', 0, 'cleaned', [
            'deleted' => $deleted,
        ], $request->user());

        $message = $deleted > 0
            ? $deleted.' '.str('log')->plural($deleted).' older than 3 days deleted.'
            : 'No logs older than 3 days to delete.';

        return back()->with('success', $message);
    }
}
