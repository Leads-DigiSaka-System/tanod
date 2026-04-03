<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractor_distributions', function (Blueprint $table) {
            // Allow multiple tractors — make tractor_id nullable, store JSON array.
            $table->json('tractor_ids')->nullable()->after('id');
            $table->foreignId('tps_id')->nullable()->after('distributed_by')->constrained('users')->nullOnDelete();
            $table->string('proof_photo')->nullable()->after('notes');
            $table->decimal('latitude', 10, 7)->nullable()->after('proof_photo');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('tractor_distributions', function (Blueprint $table) {
            $table->dropForeign(['tps_id']);
            $table->dropColumn(['tractor_ids', 'tps_id', 'proof_photo', 'latitude', 'longitude']);
        });
    }
};
