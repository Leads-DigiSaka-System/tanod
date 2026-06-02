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
        Schema::create('fca_damage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->unsignedInteger('entry_order')->default(0);
            $table->string('unit');
            $table->string('operational_after_repair', 8);
            $table->date('date_damaged');
            $table->date('date_repaired');
            $table->text('nature_of_problem');
            $table->text('cause_of_damage');
            $table->text('parts_replaced');
            $table->foreignId('in_charge_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['user_fca_id', 'entry_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fca_damage_records');
    }
};
