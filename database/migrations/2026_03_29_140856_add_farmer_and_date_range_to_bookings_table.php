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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('farmer_id')->nullable()->after('booked_by')->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable()->after('booking_date');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->decimal('farm_area_hectares', 10, 2)->nullable()->after('purpose');
            $table->text('notes')->nullable()->after('farm_area_hectares');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->dropColumn(['farmer_id', 'start_date', 'end_date', 'start_time', 'end_time', 'farm_area_hectares', 'notes']);
        });
    }
};
