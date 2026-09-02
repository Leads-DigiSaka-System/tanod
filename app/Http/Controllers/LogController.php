<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
}
