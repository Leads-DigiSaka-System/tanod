<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('maintenance_date');
            $table->string('tech_name')->nullable();
            $table->string('tech_email')->nullable();
            $table->string('tech_phone')->nullable();
            $table->string('farmer_name')->nullable();
            $table->string('farmer_email')->nullable();
            $table->string('farmer_phone')->nullable();
            $table->text('description')->nullable();
            $table->text('conclusion')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('km_at_maintenance', 10, 2)->nullable();
            $table->decimal('hours_at_maintenance', 10, 2)->nullable();
            $table->enum('status', ['documentation', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('documentation');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete()->comment('TPS user');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('maintenance_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type')->default('before')->comment('before, after, part');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_images');
        Schema::dropIfExists('maintenances');
        Schema::dropIfExists('issue_types');
    }
};
