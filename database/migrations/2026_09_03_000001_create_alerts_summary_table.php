<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('total_alerts')->default(0);
            $table->unsignedBigInteger('unacknowledged_alerts')->default(0);
            $table->json('by_type')->nullable();
            $table->timestamps();
        });

        // Seed a single row with the current alerts state so the summary is accurate
        // immediately after migration (no full scan needed on future reads).
        $total = DB::table('alerts')->count();
        $unacknowledged = DB::table('alerts')->where('is_acknowledged', false)->count();

        $byType = DB::table('alerts')
            ->select('type')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_acknowledged = 0 then 1 else 0 end) as unacknowledged')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->type => [
                    'total' => (int) $item->total,
                    'unacknowledged' => (int) $item->unacknowledged,
                ],
            ])
            ->all();

        DB::table('alerts_summary')->insert([
            'id' => 1,
            'total_alerts' => $total,
            'unacknowledged_alerts' => $unacknowledged,
            'by_type' => json_encode($byType),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts_summary');
    }
};
