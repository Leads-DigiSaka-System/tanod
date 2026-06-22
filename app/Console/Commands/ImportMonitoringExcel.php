<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use App\Models\UserFca;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

class ImportMonitoringExcel extends Command
{
    protected $signature = 'import:monitoring {--dry-run : Preview without saving}';

    protected $description = 'Import monitoring.xlsx â€” tractor/device data + R&M tickets';

    public function handle(): int
    {
        $path = public_path('assets/excel/monitoring.xlsx');

        if (! file_exists($path)) {
            $this->error("File not found: $path");

            return self::FAILURE;
        }

        $this->info("Importing: $path");
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN â€” no data will be saved');
        }

        $importer = new MonitoringMultiSheetImport($this, $this->option('dry-run'));
        Excel::import($importer, $path);

        $this->newLine();
        $this->info('Import complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tractors created', $importer->stats['tractorsCreated']],
                ['Tractors updated', $importer->stats['tractorsUpdated']],
                ['FCA records created', $importer->stats['fcaCreated']],
                ['Tickets created', $importer->stats['ticketsCreated']],
                ['Rows skipped', $importer->stats['skipped']],
            ]
        );

        return self::SUCCESS;
    }
}

// â”€â”€ Multi-sheet importer â”€â”€
class MonitoringMultiSheetImport implements WithMultipleSheets
{
    public array $stats = [
        'tractorsCreated' => 0,
        'tractorsUpdated' => 0,
        'fcaCreated' => 0,
        'ticketsCreated' => 0,
        'skipped' => 0,
    ];

    public function __construct(
        public Command $cmd,
        public bool $dryRun = false,
    ) {}

    public function sheets(): array
    {
        return [
            3 => new MonitoringSheetImport($this),  // Tractor + Device data
            2 => new TicketSheetImport($this),       // R&M Request Form (tickets)
        ];
    }

    /**
     * Clean Excel IFERROR formula artifacts from cell values.
     */
    public function extractFormulaValue(string $val): ?string
    {
        // =IFERROR(__xludf.DUMMYFUNCTION("""COMPUTED_VALUE"""),"Actual Value")
        // =IFERROR(__xludf.DUMMYFUNCTION("""COMPUTED_VALUE"""),1.0)
        if (stripos($val, '=iferror') !== false) {
            // Try to extract quoted fallback first
            if (preg_match('/,\s*"([^"]*(?:""[^"]*)*)"\s*\)/i', $val, $m)) {
                $cleaned = str_replace('""', '"', trim($m[1]));
                if ($cleaned !== '' && $cleaned !== 'COMPUTED_VALUE') {
                    return $cleaned;
                }
            }
            // Try numeric fallback: =IFERROR(..., 123) or =IFERROR(..., 13150.0)
            if (preg_match('/,\s*([\d.]+)\s*\)/i', $val, $m)) {
                $num = trim($m[1]);
                // Skip auto-generated incrementing floats (1.0, 2.0, 3.0...)
                // but keep real numbers (13150.0, 500.00, etc.)
                if (is_numeric($num)) {
                    $floatVal = (float) $num;
                    // Skip if it's a small integer float like 1.0, 2.0 (formula row counters)
                    if ($floatVal == (int) $floatVal && $floatVal < 100) {
                        return null;
                    }

                    return $num;
                }
            }

            return null; // Could not extract meaningful value
        }

        return $val;
    }
}

// â”€â”€ Monitoring Sheet (Sheet 3) â”€â”€
class MonitoringSheetImport implements ToCollection, WithChunkReading
{
    public function __construct(private MonitoringMultiSheetImport $parent) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $p = $this->parent;
        $dry = $p->dryRun;

        if ($rows->count() < 3) {
            return;
        }

        // Row 0 = blank, Row 1 = actual headers (formula fallbacks), Data from row 2+
        $headers = $rows[1]->toArray();
        $map = $this->buildHeaderMap($headers);

        for ($r = 2; $r < $rows->count(); $r++) {
            $row = $rows[$r]->toArray();

            $imei = $this->get($row, $map, ['imei']);
            $plate = $this->get($row, $map, ['number plate', 'no plate', 'plate no']);

            if (! $imei && ! $plate) {
                $p->stats['skipped']++;

                continue;
            }

            // Match tractor by IMEI first, then by plate. If found, update. If not, create new.
            $tractor = null;
            if ($imei) {
                $tractor = Tractor::where('imei', $imei)->first();
            }
            if (! $tractor && $plate) {
                $tractor = Tractor::where('no_plate', strtoupper(trim((string) $plate)))->first();
            }

            $isNew = ! $tractor;

            if (! $tractor) {
                $tractor = new Tractor;
                $tractor->imei = $imei;
            }

            // Extract Excel columns
            $recipient = $this->get($row, $map, ['recipient']);
            $address = $this->get($row, $map, ['address']);
            $serialNo = $this->get($row, $map, ['serial number', 'id_no']);
            $engineNo = $this->get($row, $map, ['engine number', 'engine_no']);
            $drNo = $this->get($row, $map, ['dr no', 'dr_no']);
            $drDate = $this->parseDate($this->get($row, $map, ['dr date', 'dr_date']));
            $deliveryDate = $this->parseDate($this->get($row, $map, ['actual delivery date']));
            $gpsStatus = $this->get($row, $map, ['gps status', 'status']);
            $plate = $this->get($row, $map, ['number plate', 'no plate', 'plate no']);

            // Fill tractor data
            $tractor->fill(array_filter([
                'name' => $recipient ?? $tractor->name,
                'no_plate' => $isNew ? ($plate ?: ($serialNo ?: $imei)) : ($tractor->no_plate),
                'installation_address' => $address ?? $tractor->installation_address,
                'id_no' => $serialNo ?: ($plate ?: $tractor->id_no),
                'engine_no' => $engineNo ?? $tractor->engine_no,
                'dr_no' => $drNo ?? $tractor->dr_no,
                'dr_date' => $drDate ?? $tractor->dr_date,
                'actual_delivery_date' => $deliveryDate ?? $tractor->actual_delivery_date,
                'is_active' => $gpsStatus
                    ? strtolower($gpsStatus) !== 'inactive'
                    : ($tractor->is_active ?? true),
            ], fn ($v) => $v !== null));

            if (! $dry) {
                $tractor->save();
            }

            if ($isNew) {
                $p->stats['tractorsCreated']++;
            } else {
                $p->stats['tractorsUpdated']++;
            }

            // Create/update FCA from Recipient column
            if ($recipient) {
                $contactPerson = $this->get($row, $map, ['contact person']);
                $project = $this->get($row, $map, ['project']);
                $region = $this->get($row, $map, ['region']);

                $fca = UserFca::where('organization_name', $recipient)->first();

                if (! $fca) {
                    $fca = new UserFca;
                    $fca->user_id = 1; // Default admin user
                    $fca->organization_name = $recipient;
                    $fca->first_name = $contactPerson ?: 'Imported';
                    $fca->last_name = 'FCA';
                    $fca->parking_latitude = 0;
                    $fca->parking_longitude = 0;
                    $fca->province = $region ?: 'Unknown';
                    $fca->city_town = $address ?: 'Unknown';
                    $fca->barangay = 'Unknown';
                    $fca->date_received = $deliveryDate ?: now()->toDateString();
                    $fca->project = $project;

                    if (! $dry) {
                        $fca->save();
                    }

                    $p->stats['fcaCreated']++;
                }
            }
        }
    }

    private function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $raw) {
            $clean = $this->cleanHeader((string) $raw);
            if ($clean) {
                $key = strtolower(trim($clean));
                $map[$key] = $idx;
                // Also store without trailing period (e.g., "dr no." → "dr no")
                if (str_ends_with($key, '.')) {
                    $map[rtrim($key, '.')] = $idx;
                }
            }
        }

        return $map;
    }

    private function cleanHeader(string $raw): ?string
    {
        // Handle formula fallback: =IFERROR(...,"Actual Name")
        // The formula quotes may be escaped as "" or \"
        if (stripos($raw, '=iferror') !== false || stripos($raw, '=IFERROR') !== false) {
            // Extract the LAST quoted string (the fallback value)
            if (preg_match('/,\s*"([^"]*(?:""[^"]*)*)"\s*\)/i', $raw, $m)) {
                return str_replace('""', '"', trim($m[1]));
            }
            // Try simpler pattern
            if (preg_match('/"([^"]+)"\s*\)\s*$/i', $raw, $m)) {
                return trim($m[1]);
            }
        }
        $raw = trim($raw);
        if ($raw === '' || $raw === '0') {
            return null;
        }

        return $raw;
    }

    private function get(array $row, array $map, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $idx = $map[$alias] ?? null;
            if ($idx !== null && isset($row[$idx]) && $row[$idx] !== null && $row[$idx] !== '') {
                $val = (string) $row[$idx];
                // Extract fallback value from IFERROR formulas
                $val = $this->parent->extractFormulaValue($val);
                if ($val !== null && $val !== '') {
                    return $val;
                }
            }
        }

        return null;
    }

    private function parseDate(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($val)->toDateTimeString();
        } catch (\Exception) {
            return null;
        }
    }
}

// ── R&M Request Form Sheet ──
class TicketSheetImport implements ToCollection, WithChunkReading
{
    public function __construct(private MonitoringMultiSheetImport $parent) {}

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $p = $this->parent;
        $dry = $p->dryRun;

        if ($rows->count() < 5) {
            return;
        }

        // Headers are on row 4 (0-indexed row 3):
        // Column K (index 10) = Subject / Issue
        // Column L (index 11) = Action Taken
        $headerRow = $rows[3]->toArray(); // Row 4 in Excel

        // Build header map from row 4
        $map = [];
        foreach ($headerRow as $idx => $raw) {
            $clean = trim((string) $raw);
            if ($clean && $clean !== '0') {
                $map[strtolower($clean)] = $idx;
            }
        }

        // Determine columns from header map (row 4 headers):
        // Column B (idx 1) = Status
        // Column C (idx 2) = FCA Name
        // Column H (idx 7) = Type of Request
        // Column J (idx 9) = Report Description (Subject)
        // Column K (idx 10) = Action Taken
        // Column S (idx 18) = Service Charge
        // Use fixed column indices since header mapping is offset by 1 column A
        $statusCol = 1;      // Column B
        $dateCol = 2;        // Column C
        $fcaNameCol = 3;     // Column D
        $typeCol = 7;        // Column H
        $subjectCol = 9;     // Column J
        $actionCol = 10;     // Column K
        $chargeCol = 19;     // Column T (service charge)

        // Preload tractor_recipients for FCA-to-tractor matching
        $fcaTractorMap = \App\Models\TractorRecipient::select('fca', 'tractor_meta_name')
            ->whereNotNull('fca')
            ->get()
            ->pluck('tractor_meta_name', 'fca')
            ->toArray();

        // Status mapping from Excel values to DB enum
        $statusMap = [
            'completed' => 'resolved',
            'complete' => 'resolved',
            'done' => 'resolved',
            'pending' => 'open',
            'open' => 'open',
            'in progress' => 'in_progress',
            'ongoing' => 'in_progress',
            'closed' => 'closed',
        ];

        // Data starts from row 5 (0-indexed row 4)
        $submitterId = User::role('super-admin')->first()?->id;
        $skipValues = ['pending', 'completed', 'status', 'viber', 'n/a', 'none', '-', ''];

        for ($r = 4; $r < $rows->count(); $r++) {
            $row = $rows[$r]->toArray();

            // Get status from column B
            $rawStatus = $row[$statusCol] ?? null;
            $excelStatus = $rawStatus ? strtolower(trim((string) $p->extractFormulaValue((string) $rawStatus))) : null;
            $ticketStatus = $statusMap[$excelStatus] ?? 'open';

            // Get reported date from column C (Excel date serial number)
            $rawDate = $row[$dateCol] ?? null;
            $reportedDate = $rawDate ? $this->parseExcelDate($p->extractFormulaValue((string) $rawDate)) : null;

            // Get FCA name from column D
            $rawFcaName = $row[$fcaNameCol] ?? null;
            $fcaName = $rawFcaName ? $p->extractFormulaValue((string) $rawFcaName) : null;

            // Get type of request from column H
            $rawType = $row[$typeCol] ?? null;
            $requestType = $rawType ? $p->extractFormulaValue((string) $rawType) : null;

            // Get subject from column J
            $rawSubject = $row[$subjectCol] ?? null;
            $subject = $rawSubject ? $p->extractFormulaValue((string) $rawSubject) : null;

            // Get action taken from column K
            $rawAction = $row[$actionCol] ?? null;
            $action = $rawAction ? $p->extractFormulaValue((string) $rawAction) : null;

            // Get service charge from column S
            $rawCharge = $row[$chargeCol] ?? null;
            $chargeVal = $rawCharge ? $p->extractFormulaValue((string) $rawCharge) : null;
            $serviceCharge = ($chargeVal && is_numeric($chargeVal) && (float) $chargeVal > 0) ? (float) $chargeVal : null;

            // Match tractor by FCA name
            $tractorName = $fcaName ? ($fcaTractorMap[$fcaName] ?? null) : null;

            if (! $subject || in_array(strtolower(trim($subject)), $skipValues)) {
                $p->stats['skipped']++;

                continue;
            }

            // Use first line as subject, rest as description
            $lines = explode("\n", (string) $subject);
            $ticketSubject = Str::limit(trim($lines[0]), 250);
            $ticketDesc = count($lines) > 1
                ? Str::limit(trim(implode("\n", array_slice($lines, 1))), 5000)
                : ($action ? Str::limit((string) $action, 5000) : null);

            $ticket = new Ticket([
                'subject' => $ticketSubject,
                'description' => $ticketDesc,
                'category' => $requestType ? Str::limit((string) $requestType, 100) : 'repair',
                'tractor_name' => $tractorName,
                'fca_name' => $fcaName ? Str::limit($fcaName, 255) : null,
                'reported_date' => $reportedDate,
                'priority' => 'medium',
                'status' => $ticketStatus,
                'service_charge' => $serviceCharge,
                'submitted_by' => $submitterId,
            ]);

            if (! $dry) {
                $ticket->save();
            }
            $p->stats['ticketsCreated']++;
        }
    }

    /**
     * Convert Excel date serial number to Y-m-d string.
     * Handles both Excel 1900 date system serials and ISO date strings.
     */
    private function parseExcelDate(?string $val): ?string
    {
        if (! $val) {
            return null;
        }

        // Already a date string like "2024-03-15"
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', trim($val))) {
            return substr(trim($val), 0, 10);
        }

        // Excel 1900 date system serial (e.g., 45334.0)
        if (is_numeric($val) && (float) $val > 365) {
            $serial = (float) $val;
            // Excel epoch: 1899-12-30 (accounting for the 1900 leap year bug)
            $unix = ($serial - 25569) * 86400;

            return date('Y-m-d', (int) $unix);
        }

        // Try carbon parse as fallback
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
