<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: which tractors belong to which group
        Schema::create('group_tractor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tractor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tractor_group_id', 'tractor_id']);
        });

        // Pivot: which devices belong to which group
        Schema::create('group_device', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tractor_group_id', 'device_id']);
        });

        // Pivot: which farmers/FCA users belong to which group
        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tractor_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('group_device');
        Schema::dropIfExists('group_tractor');
    }
};
