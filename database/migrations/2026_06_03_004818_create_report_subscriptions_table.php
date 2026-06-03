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
        Schema::create('report_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type'); // tractor-usage, maintenance-summary, booking-summary, device-status, alerts-history, ticket-summary
            $table->string('interval'); // daily, weekly, monthly
            $table->string('day_of_week')->nullable(); // for weekly: monday, tuesday, etc.
            $table->string('day_of_month')->nullable(); // for monthly: 1-28
            $table->string('time')->default('08:00'); // HH:MM 24h format
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_scheduled_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'report_type', 'interval'], 'uq_report_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_subscriptions');
    }
};
