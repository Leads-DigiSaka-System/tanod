<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReport;
use App\Models\UserFca;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiTicketReportController extends Controller
{
    /**
     * List ticket reports for the TPS user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $reports = TicketReport::with(['ticket:id,subject,status'])
            ->where('tps_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($reports);
    }

    /**
     * Show a single ticket report.
     */
    public function show(Request $request, TicketReport $ticketReport)
    {
        $user = $request->user();

        abort_unless($ticketReport->tps_id === $user->id, 403);

        $ticketReport->load(['ticket:id,subject,status,description,resolved_at,resolution_photo_path,dr_photo_paths,service_charge,down_payment,installments']);

        return response()->json(['data' => $this->formatReport($ticketReport)]);
    }

    /**
     * Update a ticket report (add/edit findings, job done, recommendation, remarks).
     */
    public function update(Request $request, TicketReport $ticketReport)
    {
        $user = $request->user();

        abort_unless($ticketReport->tps_id === $user->id, 403);

        $data = $request->validate([
            'findings' => 'nullable|string|max:5000',
            'job_done' => 'nullable|string|max:5000',
            'recommendation' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:5000',
            'customer_address' => 'nullable|string|max:2000',
            'contact_no' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'machine_hours' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'warranty_type' => 'nullable|string|max:100',
            'service_performed' => 'nullable|array',
            'service_performed.*' => 'string|max:50',
            'repair_start_date' => 'nullable|date',
            'repair_end_date' => 'nullable|date|after_or_equal:repair_start_date',
            'service_charge' => 'nullable|numeric|min:0|max:99999999.99',
            'down_payment' => 'nullable|numeric|min:0|max:99999999.99',
            'installments' => 'nullable|integer|min:1|max:12',
            'status' => 'nullable|in:draft,finalized',
        ]);

        $ticketReport->update($data);

        // If status changed to finalized, regenerate PDF
        if (($data['status'] ?? null) === 'finalized' || $request->boolean('regenerate_pdf')) {
            $this->generatePdf($ticketReport);
        }

        return response()->json(['data' => $this->formatReport($ticketReport->fresh())]);
    }

    /**
     * Download the report PDF.
     */
    public function downloadPdf(Request $request, TicketReport $ticketReport)
    {
        $user = $request->user();

        abort_unless($ticketReport->tps_id === $user->id, 403);

        // Generate PDF if not yet generated
        if (! $ticketReport->report_pdf_path) {
            $this->generatePdf($ticketReport);
            $ticketReport->refresh();
        }

        $path = storage_path('app/public/'.$ticketReport->report_pdf_path);

        abort_unless(file_exists($path), 404, 'PDF not found.');

        return response()->download($path, 'ticket-report-'.$ticketReport->id.'.pdf');
    }

    /**
     * Return FCA contacts and tractor details for the ticket's report form.
     */
    public function reportFormData(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Get the FCA user who submitted the ticket
        $submitter = $ticket->submitter;
        $fcaProfile = null;
        $contacts = [];
        $tractorDetails = null;

        if ($submitter) {
            // Try to get the FCA profile. The submitter might be the FCA themselves
            // or a farmer under an FCA.
            $fcaUserId = $submitter->fca_id ?? $submitter->id;
            $fcaProfile = UserFca::where('user_id', $fcaUserId)->first();

            if ($fcaProfile) {
                $contacts = $fcaProfile->alternativeContacts()
                    ->get()
                    ->map(fn ($contact) => [
                        'id' => $contact->id,
                        'phone' => $contact->phone,
                        'name' => trim($contact->first_name.' '.$contact->last_name),
                        'position' => $contact->position,
                    ])
                    ->values()
                    ->all();
            }

            // Always try to get tractor details from the tractors table
            if ($ticket->tractor_id) {
                $tractor = \App\Models\Tractor::find($ticket->tractor_id);
                if ($tractor) {
                    $tractorDetails = [
                        'serial_number' => $tractor->id_no,
                        'front_loader_serial' => $tractor->front_loader_sn,
                        'rotavator_serial' => $tractor->rotary_tiller_sn,
                        'disk_plow_serial' => $tractor->disc_plow_sn,
                        'engine_number' => $tractor->engine_no,
                    ];
                }
            }
        }

        // Also include the submitter's own phone as a contact option
        $submitterPhone = $submitter?->phone;

        return response()->json([
            'data' => [
                'contacts' => $contacts,
                'submitter_phone' => $submitterPhone,
                'tractor_details' => $tractorDetails,
                'fca_name' => $fcaProfile ? trim(($fcaProfile->first_name ?? '').' '.($fcaProfile->last_name ?? '')) : ($submitter?->name ?? null),
                'fca_address' => $fcaProfile
                    ? collect([$fcaProfile->barangay, $fcaProfile->city_town, $fcaProfile->province])
                        ->filter()
                        ->implode(', ')
                    : null,
            ],
        ]);
    }

    /**
     * Generate PDF for the report.
     */
    private function generatePdf(TicketReport $report): void
    {
        $report->load(['ticket', 'tps']);

        // Ensure the reports directory exists
        Storage::disk('public')->makeDirectory('reports');

        $filename = 'reports/ticket-report-'.$report->id.'.pdf';
        $pdf = Pdf::loadView('pdf.ticket-report', ['report' => $report]);
        $pdf->save(storage_path('app/public/'.$filename));

        $report->update([
            'report_pdf_path' => $filename,
            'generated_at' => now(),
        ]);
    }

    /**
     * Format report for API response.
     */
    private function formatReport(TicketReport $report): array
    {
        return [
            'id' => $report->id,
            'ticket_id' => $report->ticket_id,
            'tps_id' => $report->tps_id,
            'ticket_no' => $report->ticket_no,
            'subject' => $report->subject,
            'category' => $report->category,
            'fca_name' => $report->fca_name,
            'submitted_by_name' => $report->submitted_by_name,
            'customer_address' => $report->customer_address,
            'contact_no' => $report->contact_no,
            'customer_name' => $report->customer_name,
            'tractor_plate' => $report->tractor_plate,
            'tractor_brand' => $report->tractor_brand,
            'tractor_model' => $report->tractor_model,
            'machine_hours' => $report->machine_hours,
            'serial_number' => $report->serial_number,
            'warranty_type' => $report->warranty_type,
            'service_performed' => $report->service_performed,
            'repair_start_date' => $report->repair_start_date?->format('Y-m-d'),
            'repair_end_date' => $report->repair_end_date?->format('Y-m-d'),
            'findings' => $report->findings,
            'job_done' => $report->job_done,
            'recommendation' => $report->recommendation,
            'remarks' => $report->remarks,
            'service_charge' => $report->service_charge,
            'down_payment' => $report->down_payment,
            'installments' => $report->installments,
            'parts_total' => $report->parts_total,
            'parts_details' => $report->parts_details,
            'resolution_photo_url' => $report->resolution_photo_url,
            'dr_photo_urls' => $report->dr_photo_urls,
            'status' => $report->status,
            'report_pdf_url' => $report->report_pdf_path
                ? asset('storage/'.$report->report_pdf_path)
                : null,
            'generated_at' => $report->generated_at?->toISOString(),
            'created_at' => $report->created_at->toISOString(),
            'updated_at' => $report->updated_at->toISOString(),
        ];
    }
}
