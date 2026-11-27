<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tractor_service_area_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->date('local_date');
            $table->string('algorithm', 16);
            $table->boolean('has_usable_gps');
            $table->json('cell_ids');
            $table->unique(['device_id', 'local_date']);
            $table->index(['algorithm', 'local_date', 'device_id'], 'tractor_service_days_algorithm_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tractor_service_area_days');
    }
};
