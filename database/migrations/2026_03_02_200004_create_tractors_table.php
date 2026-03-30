<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tractors', function (Blueprint $table) {
            $table->id();
            $table->string('imei')->nullable()->index();
            $table->string('no_plate')->nullable();
            $table->string('id_no')->nullable()->comment('serial/id number');
            $table->string('engine_no')->nullable();
            $table->string('chassis_no')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('fuel_consumption', 8, 2)->nullable()->comment('liters per 100km');
            $table->date('manufacture_date')->nullable();
            $table->timestamp('installation_time')->nullable();
            $table->string('installation_address')->nullable();
            $table->decimal('max_speed', 8, 2)->nullable();
            $table->decimal('maintenance_km', 10, 2)->nullable()->comment('km interval for maintenance');
            $table->decimal('maintenance_hours', 10, 2)->nullable()->comment('hours interval for maintenance');
            $table->decimal('total_distance', 12, 2)->default(0);
            $table->decimal('running_hours', 10, 2)->default(0);
            $table->date('insurance_effective_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('dr_no')->nullable();
            $table->date('dr_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('front_loader_sn')->nullable();
            $table->string('rotary_tiller_sn')->nullable();
            $table->string('disc_plow_sn')->nullable();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('tractor_groups')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->comment('FCA/Coop user');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tractors');
    }
};
