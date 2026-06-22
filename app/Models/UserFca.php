<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFca extends Model
{
    use HasFactory;

    protected $table = 'users_fca';

    protected $fillable = [
        'user_id',
        'organization_name',
        'first_name',
        'last_name',
        'parking_latitude',
        'parking_longitude',
        'province',
        'city_town',
        'barangay',
        'date_received',
        'project',
        'tractor_photo_paths',
        'logbook_photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'parking_latitude' => 'float',
            'parking_longitude' => 'float',
            'date_received' => 'date',
            'tractor_photo_paths' => 'array',
            'logbook_photo_paths' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function machineHours()
    {
        return $this->hasMany(FcaMachineHour::class)->orderBy('entry_order')->orderBy('id');
    }

    public function tractorDetail()
    {
        return $this->hasOne(FcaTractorDetail::class);
    }

    public function alternativeContacts()
    {
        return $this->hasMany(FcaAlternativeContact::class)->orderBy('entry_order')->orderBy('id');
    }

    public function profilePhotos()
    {
        return $this->hasMany(FcaProfilePhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function surveyAnswers()
    {
        return $this->hasMany(FcaSurveyAnswer::class)
            ->orderBy('question_number')
            ->orderBy('entry_order')
            ->orderBy('id');
    }

    public function pmsRecords()
    {
        return $this->hasMany(FcaPmsRecord::class)->orderBy('column_order')->orderBy('id');
    }

    public function damageRecords()
    {
        return $this->hasMany(FcaDamageRecord::class)->orderBy('entry_order')->orderBy('id');
    }
}
