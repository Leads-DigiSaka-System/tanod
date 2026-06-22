<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorRecipient extends Model
{
    protected $fillable = [
        'source_id',
        'fca',
        'mobile_number',
        'email',
        'last_name',
        'first_name',
        'province_code',
        'province_description',
        'city_code',
        'city_name',
        'barangay_id',
        'barangay_name',
        'date_received',
        'park_latitude',
        'park_longitude',
        'park_address',
        'tractor_id',
        'tractor_meta_name',
        'front_loader_serial_number',
        'dr_no',
        'rotavator_serial_number',
        'serial_number',
        'disk_serial_number',
        'engine_number',
        'gps_imei',
        'gps_sim_no',
        'gps_mobile_no',
        'alternative_contacts',
        'logbook_photos',
        'survey',
        'pms',
        'damage_records',
        'machine_hours',
        'tps_id',
        'tps_full_name',
        'tps_mobile',
        'tps_email',
        'photos',
        'is_submitted',
        'source_created_at',
        'source_updated_at',
        'source_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'alternative_contacts' => 'array',
            'logbook_photos' => 'array',
            'survey' => 'array',
            'pms' => 'array',
            'damage_records' => 'array',
            'machine_hours' => 'array',
            'is_submitted' => 'boolean',
            'source_created_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'source_deleted_at' => 'datetime',
        ];
    }

    /**
     * Computed full name of the recipient.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    /**
     * Parse photos string into an array of filenames.
     */
    public function getPhotoFilesAttribute(): array
    {
        if (empty($this->photos)) {
            return [];
        }

        return array_filter(explode(' ', trim($this->photos)));
    }
}
