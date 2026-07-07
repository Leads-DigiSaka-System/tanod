<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
        });

        // Add collectible tracking columns to tickets table
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'collectible_status')) {
                $table->string('collectible_status', 20)->default('collectible')
                    ->after('installments')
                    ->comment('collectible, to_approve, paid');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_payments');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['collectible_status']);
        });
    }
};
