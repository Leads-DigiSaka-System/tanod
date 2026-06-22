<?php

namespace App\Console\Commands;

use App\Models\TractorRecipient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchTractorRecipients extends Command
{
    protected $signature = 'app:fetch-tractor-recipients';

    protected $description = 'Fetch tractor recipients from Digisaka API and upsert into local database';

    public function handle(): int
    {
        $baseUrl = config('services.digisaka.base_url', 'http://digisaka.app');
        $token = config('services.digisaka.token', '2550|A69nLrYeGgcJkVjiuVlK9WrF0pBbnNb3JpTsEN93002dd78a');

        $this->info("Fetching tractor recipients from {$baseUrl}...");

        $response = Http::withToken($token)
            ->timeout(60)
            ->acceptJson()
            ->get("{$baseUrl}/api/outbound/tractor-recipients");

        if (! $response->successful()) {
            $this->error('Failed to fetch: HTTP '.$response->status());

            return self::FAILURE;
        }

        $json = $response->json();

        // Updated API returns { data: { recipients: [...] } } with no pagination
        $records = $json['data']['recipients'] ?? $json['data']['data'] ?? $json['data'] ?? $json;

        if (! is_array($records) || empty($records)) {
            $this->warn('No records found in API response.');

            return self::FAILURE;
        }

        $this->info('  Records received: '.count($records));

        $synced = $this->upsertBatch($records);

        $this->newLine();
        $this->info("✓ Done! {$synced} record(s) synced.");

        return self::SUCCESS;
    }

    /**
     * Upsert a batch of records from the API.
     *
     * Uses source_id as the unique key to prevent duplicates
     * and update existing records when the API data changes.
     */
    private function upsertBatch(array $records): int
    {
        $now = now();
        $rows = [];

        // Preload location lookups for resolving codes to names
        $provinceNames = $this->loadProvinceNames();
        $cityNames = $this->loadCityNames();
        $barangayNames = $this->loadBarangayNames();

        foreach ($records as $r) {
            // Handle nested objects (tractor, tps) and flat strings (province, city, barangay)
            $province = $r['province'] ?? null;
            $city = $r['city'] ?? null;
            $barangay = $r['barangay'] ?? null;

            $provinceCode = is_array($province) ? ($province['province_code'] ?? null) : $province;
            $cityCode = is_array($city) ? ($city['city_code'] ?? null) : $city;
            $barangayId = is_array($barangay) ? ($barangay['id'] ?? null) : (is_numeric($barangay) ? (int) $barangay : null);

            $rows[] = [
                'source_id' => $r['id'] ?? null,
                'fca' => $r['fca'] ?? null,
                'mobile_number' => $r['mobile_number'] ?? null,
                'email' => $r['email'] ?? null,
                'last_name' => $r['last_name'] ?? null,
                'first_name' => $r['first_name'] ?? null,
                'province_code' => $provinceCode,
                'province_description' => $provinceNames[$provinceCode] ?? null,
                'city_code' => $cityCode,
                'city_name' => $cityNames[$cityCode] ?? null,
                'barangay_id' => $barangayId,
                'barangay_name' => $barangayNames[$barangayId] ?? null,
                'date_received' => $this->parseDate($r['date_received'] ?? null),
                'park_latitude' => $r['park_latitude'] ?? null,
                'park_longitude' => $r['park_longitude'] ?? null,
                'park_address' => $r['park_address'] ?? null,
                'tractor_id' => $r['tractor_id'] ?? null,
                'tractor_meta_name' => is_array($r['tractor'] ?? null) ? ($r['tractor']['meta_name'] ?? null) : ($r['tractor_meta_name'] ?? null),
                'front_loader_serial_number' => $r['front_loader_serial_number'] ?? null,
                'dr_no' => $r['dr_no'] ?? null,
                'rotavator_serial_number' => $r['rotavator_serial_number'] ?? null,
                'serial_number' => $r['serial_number'] ?? null,
                'disk_serial_number' => $r['disk_serial_number'] ?? null,
                'engine_number' => $r['engine_number'] ?? null,
                'gps_imei' => $r['gps_imei'] ?? null,
                'gps_sim_no' => $r['gps_sim_no'] ?? null,
                'gps_mobile_no' => $r['gps_mobile_no'] ?? null,
                'alternative_contacts' => $this->castJson($r['alternative_contacts'] ?? null),
                'logbook_photos' => $this->castJson($r['logbook_photos'] ?? null),
                'survey' => $this->castJson($r['survey'] ?? null),
                'pms' => $this->castJson($r['pms'] ?? null),
                'damage_records' => $this->castJson($r['damage_records'] ?? null),
                'machine_hours' => $this->castJson($r['machine_hours'] ?? null),
                'tps_id' => $r['tps_id'] ?? null,
                'tps_full_name' => is_array($r['tps'] ?? null) ? ($r['tps']['full_name'] ?? null) : ($r['tps_full_name'] ?? null),
                'tps_mobile' => is_array($r['tps'] ?? null) ? ($r['tps']['mobile'] ?? null) : ($r['tps_mobile'] ?? null),
                'tps_email' => is_array($r['tps'] ?? null) ? ($r['tps']['email'] ?? null) : ($r['tps_email'] ?? null),
                'photos' => is_array($r['photos'] ?? null) ? implode(' ', $r['photos']) : ($r['photos'] ?? null),
                'is_submitted' => (bool) ($r['is_submitted'] ?? false),
                'source_created_at' => $this->parseTimestamp($r['created_at'] ?? null),
                'source_updated_at' => $this->parseTimestamp($r['updated_at'] ?? null),
                'source_deleted_at' => $this->parseTimestamp($r['deleted_at'] ?? null),
                'updated_at' => $now,
            ];
        }

        // Filter out records without a source_id
        $rows = array_values(array_filter($rows, fn ($r) => ! is_null($r['source_id'])));

        if (empty($rows)) {
            return 0;
        }

        // upsert() returns "affected rows" — MySQL counts updates as 2 per row,
        // so we return the actual record count instead for accurate reporting.
        TractorRecipient::upsert(
            $rows,
            ['source_id'],
            array_keys($rows[0])
        );

        return count($rows);
    }

    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Already a date string like "2025-01-30"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return date('Y-m-d', strtotime($value)) ?: null;
    }

    private function parseTimestamp(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime($value)) ?: null;
    }

    /**
     * Ensure the value is a proper JSON representation.
     */
    private function castJson(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'System.Object[]' || $value === []) {
            return null;
        }

        if (is_string($value)) {
            // Already a JSON string
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }

            // Not valid JSON — store as a single-element array
            return json_encode([$value]);
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return json_encode([$value]);
    }

    /**
     * Load province descriptions keyed by province_code.
     *
     * @return array<string, string>
     */
    private function loadProvinceNames(): array
    {
        return \Illuminate\Support\Facades\DB::table('philippine_provinces')
            ->pluck('province_description', 'province_code')
            ->toArray();
    }

    /**
     * Load city descriptions keyed by city_municipality_code.
     *
     * @return array<string, string>
     */
    private function loadCityNames(): array
    {
        return \Illuminate\Support\Facades\DB::table('philippine_cities')
            ->pluck('city_municipality_description', 'city_municipality_code')
            ->toArray();
    }

    /**
     * Load barangay descriptions keyed by id.
     *
     * @return array<int, string>
     */
    private function loadBarangayNames(): array
    {
        return \Illuminate\Support\Facades\DB::table('philippine_barangays')
            ->pluck('barangay_description', 'id')
            ->toArray();
    }
}
