<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tractor_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distributed_to')->constrained('users')->cascadeOnDelete()->comment('FCA/Coop user');
            $table->foreignId('distributed_by')->constrained('users')->cascadeOnDelete()->comment('TPS user');
            $table->string('area')->nullable();
            $table->text('notes')->nullable();
            $table->date('distribution_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['distributed', 'returned', 'cancelled'])->default('distributed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tractor_distributions');
    }
};
