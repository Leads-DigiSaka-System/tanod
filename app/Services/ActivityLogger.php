<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    /**
     * Record a system activity entry.
     *
     * @param  int|string  $modelId
     */
    public static function log(string $modelType, $modelId, string $action, ?array $changes = null, $user = null): ActivityLog
    {
        return ActivityLog::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'action' => $action,
            'changes' => $changes,
            'performed_by' => $user?->id ?? auth()->id(),
        ]);
    }
}
