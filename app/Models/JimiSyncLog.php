<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JimiSyncLog extends Model
{
    protected $fillable = [
        'method',
        'status',
        'records_fetched',
        'records_stored',
        'error_message',
        'duration_ms',
    ];
}
