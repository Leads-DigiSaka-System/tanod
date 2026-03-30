<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tractor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('geo_fence_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->comment('geofence_breach, maintenance_due, inactive, speed, custom');
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
