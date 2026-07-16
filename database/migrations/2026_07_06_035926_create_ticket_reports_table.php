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
        Schema::create('ticket_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tps_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_no')->nullable();
            $table->string('subject');
            $table->string('category')->nullable();
            $table->string('fca_name')->nullable();
            $table->string('submitted_by_name')->nullable();
            $table->string('tractor_plate')->nullable();
            $table->string('tractor_brand')->nullable();
            $table->string('tractor_model')->nullable();
            $table->text('findings')->nullable();
            $table->text('job_done')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('service_charge', 10, 2)->nullable();
            $table->decimal('down_payment', 10, 2)->nullable();
            $table->integer('installments')->nullable();
            $table->decimal('parts_total', 10, 2)->nullable();
            $table->json('parts_details')->nullable();
            $table->string('resolution_photo_url')->nullable();
            $table->json('dr_photo_urls')->nullable();
            $table->string('status')->default('draft'); // draft, finalized
            $table->string('report_pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_reports');
    }
};
