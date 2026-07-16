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
            if (! Schema::hasColumn('ticket_reports', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('submitted_by_name');
            }
            if (! Schema::hasColumn('ticket_reports', 'contact_no')) {
                $table->string('contact_no', 50)->nullable()->after('customer_address');
            }
            if (! Schema::hasColumn('ticket_reports', 'machine_hours')) {
                $table->string('machine_hours', 100)->nullable()->after('tractor_model');
            }
            if (! Schema::hasColumn('ticket_reports', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->after('machine_hours');
            }
            if (! Schema::hasColumn('ticket_reports', 'warranty_type')) {
                $table->string('warranty_type', 100)->nullable()->after('serial_number');
            }
            if (! Schema::hasColumn('ticket_reports', 'service_performed')) {
                $table->json('service_performed')->nullable()->after('warranty_type');
            }
            if (! Schema::hasColumn('ticket_reports', 'repair_start_date')) {
                $table->date('repair_start_date')->nullable()->after('service_performed');
            }
            if (! Schema::hasColumn('ticket_reports', 'repair_end_date')) {
                $table->date('repair_end_date')->nullable()->after('repair_start_date');
            }
            if (! Schema::hasColumn('ticket_reports', 'customer_name')) {
                $table->string('customer_name', 255)->nullable()->after('remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_reports', function (Blueprint $table) {
            $table->dropColumn([
                'customer_address',
                'contact_no',
                'machine_hours',
                'serial_number',
                'warranty_type',
                'service_performed',
                'repair_start_date',
                'repair_end_date',
                'customer_name',
            ]);
        });
    }
};
