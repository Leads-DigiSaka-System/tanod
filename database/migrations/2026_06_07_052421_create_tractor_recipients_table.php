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
        Schema::create('tractor_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->unique()->comment('ID from Digisaka API');
            $table->string('fca')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email')->nullable();
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('province_description')->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('city_name')->nullable();
            $table->unsignedBigInteger('barangay_id')->nullable();
            $table->string('barangay_name')->nullable();
            $table->date('date_received')->nullable();
            $table->string('park_latitude', 30)->nullable();
            $table->string('park_longitude', 30)->nullable();
            $table->text('park_address')->nullable();
            $table->unsignedBigInteger('tractor_id')->nullable();
            $table->string('tractor_meta_name')->nullable();
            $table->string('front_loader_serial_number')->nullable();
            $table->string('dr_no')->nullable();
            $table->string('rotavator_serial_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('disk_serial_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('gps_imei', 30)->nullable();
            $table->string('gps_sim_no', 30)->nullable();
            $table->string('gps_mobile_no', 20)->nullable();
            $table->json('alternative_contacts')->nullable();
            $table->json('logbook_photos')->nullable();
            $table->json('survey')->nullable();
            $table->json('pms')->nullable();
            $table->unsignedBigInteger('tps_id')->nullable();
            $table->string('tps_full_name')->nullable();
            $table->string('tps_mobile', 20)->nullable();
            $table->string('tps_email')->nullable();
            $table->text('photos')->nullable()->comment('Space-separated photo filenames');
            $table->boolean('is_submitted')->default(false);
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('source_deleted_at')->nullable();
            $table->timestamps();

            $table->index('tractor_id');
            $table->index('tps_id');
            $table->index('province_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tractor_recipients');
    }
};
