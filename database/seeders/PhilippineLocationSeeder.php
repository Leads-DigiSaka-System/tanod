<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class PhilippineLocationSeeder extends Seeder
{
    public function run(): void
    {
        $sqlFilesByTable = [
            'philippine_regions' => public_path('assets/sql/philippine_regions.sql'),
            'philippine_provinces' => public_path('assets/sql/philippine_provinces.sql'),
            'philippine_cities' => public_path('assets/sql/philippine_cities.sql'),
            'philippine_barangays' => public_path('assets/sql/philippine_barangays.sql'),
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (array_reverse(array_keys($sqlFilesByTable)) as $table) {
                DB::table($table)->truncate();
            }

            foreach ($sqlFilesByTable as $path) {
                if (! File::exists($path)) {
                    throw new RuntimeException("SQL seed file not found: {$path}");
                }

                DB::unprepared(File::get($path));
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
