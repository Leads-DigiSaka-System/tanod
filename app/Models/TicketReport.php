<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReport extends Model
{
    protected $fillable = [
        'ticket_id',
        'tps_id',
        'ticket_no',
        'subject',
        'category',
        'fca_name',
        'submitted_by_name',
        'customer_address',
        'contact_no',
        'customer_name',
        'tractor_plate',
        'tractor_brand',
        'tractor_model',
        'machine_hours',
        'serial_number',
        'warranty_type',
        'service_performed',
        'repair_start_date',
        'repair_end_date',
        'findings',
        'job_done',
        'recommendation',
        'remarks',
        'service_charge',
        'down_payment',
        'installments',
        'parts_total',
        'parts_details',
        'resolution_photo_url',
        'dr_photo_urls',
        'status',
        'work_status',
        'work_condition',
        'report_pdf_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'service_charge' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'installments' => 'integer',
            'parts_total' => 'decimal:2',
            'parts_details' => 'array',
            'dr_photo_urls' => 'array',
            'service_performed' => 'array',
            'repair_start_date' => 'date',
            'repair_end_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tps_id');
    }
}
