<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->json('pms_checklist')->nullable()->after('hours_at_maintenance');
            $table->foreignId('requested_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->text('request_notes')->nullable()->after('requested_by');
        });

        // Seed PMS issue types
        $items = [
            'Engine Oil',
            'Oil Filter',
            'Hydraulic Oil',
            'Hydraulic Filter',
            'Fuel Filter',
            'Greasing',
            'Brake Inspection',
            'Tire',
            'Battery',
        ];

        $now = now();
        foreach ($items as $name) {
            DB::table('issue_types')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('issue_types')->whereIn('name', [
            'Engine Oil', 'Oil Filter', 'Hydraulic Oil', 'Hydraulic Filter',
            'Fuel Filter', 'Greasing', 'Brake Inspection', 'Tire', 'Battery',
        ])->delete();

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['pms_checklist', 'requested_by', 'request_notes']);
        });
    }
};
