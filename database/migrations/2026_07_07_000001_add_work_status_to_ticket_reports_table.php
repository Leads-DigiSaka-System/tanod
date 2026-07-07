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
            if (! Schema::hasColumn('ticket_reports', 'work_status')) {
                $table->string('work_status', 50)->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_reports', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_reports', 'work_status')) {
                $table->dropColumn('work_status');
            }
        });
    }
};
