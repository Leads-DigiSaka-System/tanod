<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('imei')->unique();
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('sim')->nullable();
            $table->string('sim_iccid')->nullable();
            $table->string('sim_registration_code')->nullable();
            $table->string('mc_type')->nullable();
            $table->string('mc_type_use_scope')->nullable();
            $table->string('mobile_data_load')->nullable();
            $table->timestamp('activation_time')->nullable();
            $table->timestamp('sales_time')->nullable();
            $table->integer('subscription_expiration')->nullable()->comment('days');
            $table->timestamp('expiration_date')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
