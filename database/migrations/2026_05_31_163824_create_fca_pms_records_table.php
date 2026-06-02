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
        Schema::create('fca_pms_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->unsignedInteger('column_order')->default(0);
            $table->unsignedInteger('actual_hours');
            $table->string('performed_by', 32);
            $table->foreignId('in_charge_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['user_fca_id', 'column_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fca_pms_records');
    }
};
