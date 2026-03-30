<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create('device_geo_fence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_fence_id')->constrained('geo_fences')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['geo_fence_id', 'device_id']);
        });

        // 2. Migrate existing data from geo_fences.device_id into pivot
        $existing = DB::table('geo_fences')->whereNotNull('device_id')->get(['id', 'device_id']);
        foreach ($existing as $row) {
            DB::table('device_geo_fence')->insert([
                'geo_fence_id' => $row->id,
                'device_id' => $row->device_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop old device_id column
        Schema::table('geo_fences', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('geo_fences', function (Blueprint $table) {
            $table->foreignId('device_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Restore first device from pivot
        $pivots = DB::table('device_geo_fence')
            ->select('geo_fence_id', DB::raw('MIN(device_id) as device_id'))
            ->groupBy('geo_fence_id')
            ->get();

        foreach ($pivots as $p) {
            DB::table('geo_fences')->where('id', $p->geo_fence_id)->update(['device_id' => $p->device_id]);
        }

        Schema::dropIfExists('device_geo_fence');
    }
};
