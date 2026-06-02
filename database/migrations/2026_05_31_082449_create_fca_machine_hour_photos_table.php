<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fca_machine_hour_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fca_machine_hour_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['fca_machine_hour_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fca_machine_hour_photos');
    }
};
