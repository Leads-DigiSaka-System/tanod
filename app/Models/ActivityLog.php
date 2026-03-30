<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'changes',
        'performed_by',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
