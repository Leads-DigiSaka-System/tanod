<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('tractor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('device_track_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('imei')->index();
            $table->decimal('start_lat', 10, 7)->nullable();
            $table->decimal('start_lng', 10, 7)->nullable();
            $table->decimal('end_lat', 10, 7)->nullable();
            $table->decimal('end_lng', 10, 7)->nullable();
            $table->decimal('mileage', 12, 2)->default(0)->comment('km');
            $table->integer('run_time_seconds')->default(0);
            $table->decimal('max_speed', 8, 2)->default(0);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('action')->comment('created, updated, deleted');
            $table->json('changes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['model_type', 'model_id']);
        });

        Schema::create('jimi_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method')->comment('API method called');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->integer('records_fetched')->default(0);
            $table->integer('records_stored')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jimi_sync_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('device_track_records');
        Schema::dropIfExists('farm_assets');
    }
};
