<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcaSurveyAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_fca_id',
        'question_number',
        'entry_order',
        'answer_text',
        'boolean_answer',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'entry_order' => 'integer',
            'boolean_answer' => 'boolean',
        ];
    }

    public function userFca()
    {
        return $this->belongsTo(UserFca::class);
    }
    //
}
