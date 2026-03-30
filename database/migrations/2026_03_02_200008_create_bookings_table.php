<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('booking_slots')->nullOnDelete();
            $table->foreignId('booked_by')->constrained('users')->cascadeOnDelete()->comment('Farmer/Renter');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('FCA/Coop user');
            $table->date('booking_date');
            $table->text('purpose')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('kilometer', 10, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_use', 'completed', 'cancelled'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_slots');
    }
};
