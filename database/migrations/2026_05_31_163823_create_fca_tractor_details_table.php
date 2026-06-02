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
        Schema::create('fca_tractor_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->foreignId('tractor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tractor_model');
            $table->string('front_loader_serial_number')->nullable();
            $table->string('dr_number')->nullable();
            $table->string('rotavator_serial_number')->nullable();
            $table->string('serial_number');
            $table->string('disk_plow_serial_number')->nullable();
            $table->string('engine_number');
            $table->string('gps_imei')->nullable();
            $table->string('gps_sim_number', 16)->nullable();
            $table->string('gps_mobile_number', 11)->nullable();
            $table->timestamps();

            $table->unique('user_fca_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fca_tractor_details');
    }
};
