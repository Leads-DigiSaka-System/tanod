<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fca_machine_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->unsignedSmallInteger('entry_order')->default(0);
            $table->date('date_visited');
            $table->unsignedInteger('machine_hours');
            $table->string('gps_status', 32);
            $table->foreignId('in_charge_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_fca_id', 'entry_order']);
            $table->index(['user_fca_id', 'date_visited']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fca_machine_hours');
    }
};
