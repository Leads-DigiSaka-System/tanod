<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('imei')->index();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('speed', 8, 2)->default(0);
            $table->string('direction')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=offline,1=online');
            $table->tinyInteger('acc_status')->default(0)->comment('0=off,1=on');
            $table->integer('gps_num')->default(0);
            $table->string('pos_type')->nullable();
            $table->timestamp('heartbeat_at')->nullable()->comment('hbTime from JIMI');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_locations');
    }
};
