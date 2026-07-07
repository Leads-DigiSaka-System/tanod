<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_reports', 'work_condition')) {
                $table->string('work_condition', 50)->nullable()->after('work_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_reports', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_reports', 'work_condition')) {
                $table->dropColumn('work_condition');
            }
        });
    }
};
