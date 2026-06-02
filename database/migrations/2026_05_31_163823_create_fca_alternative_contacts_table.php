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
        Schema::create('fca_alternative_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->unsignedInteger('entry_order')->default(0);
            $table->string('phone', 20);
            $table->string('last_name');
            $table->string('first_name');
            $table->string('position');
            $table->timestamps();

            $table->index(['user_fca_id', 'entry_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fca_alternative_contacts');
    }
};
