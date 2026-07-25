<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->inferredRegions() as $groupId => $region) {
            DB::table('tractor_groups')
                ->where('id', $groupId)
                ->where(function ($query): void {
                    $query->whereNull('region')->orWhere('region', '');
                })
                ->update(['region' => $region]);
        }
    }

    public function down(): void
    {
        // Preserve valid region assignments because groups may be edited after this backfill runs.
    }

    /**
     * @return array<int, string>
     */
    private function inferredRegions(): array
    {
        $provinceRegions = DB::table('philippine_provinces as provinces')
            ->join('philippine_regions as regions', 'regions.region_code', '=', 'provinces.region_code')
            ->pluck('regions.region_number', 'provinces.province_description')
            ->mapWithKeys(fn (string $region, string $province): array => [
                $this->normalizeLocation($province) => $region,
            ]);

        $regionAliases = DB::table('philippine_regions')
            ->get(['region_number', 'region_description'])
            ->flatMap(fn (object $region): array => [
                $this->normalizeLocation($region->region_number) => $region->region_number,
                $this->normalizeLocation($region->region_description) => $region->region_number,
            ]);

        $locationRegions = $provinceRegions->merge($regionAliases);

        return DB::table('tractor_groups')
            ->where(function ($query): void {
                $query->whereNull('region')->orWhere('region', '');
            })
            ->get(['id', 'name', 'area'])
            ->mapWithKeys(function (object $group) use ($locationRegions): array {
                foreach (array_filter([$group->area, $group->name]) as $location) {
                    $region = $locationRegions->get($this->normalizeLocation($location));

                    if ($region) {
                        return [$group->id => $region];
                    }
                }

                return [];
            })
            ->all();
    }

    private function normalizeLocation(string $location): string
    {
        return Str::of($location)
            ->lower()
            ->replace(['_', '-'], ' ')
            ->replaceMatches('/\s+(?:v?\d+)+$/i', '')
            ->squish()
            ->value();
    }
};
