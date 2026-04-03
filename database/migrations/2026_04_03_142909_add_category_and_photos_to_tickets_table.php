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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('status');
            $table->string('photo_path')->nullable()->after('category');
            $table->string('resolution_photo_path')->nullable()->after('photo_path');
            $table->text('resolution_notes')->nullable()->after('resolution_photo_path');
            $table->foreignId('resolved_by')->nullable()->after('resolution_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn([
                'category',
                'photo_path',
                'resolution_photo_path',
                'resolution_notes',
                'resolved_by',
                'resolved_at',
            ]);
        });
    }
};
