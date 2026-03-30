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
        // Drop the group_device pivot — devices are reachable through tractors
        Schema::dropIfExists('group_device');

        // Remove the direct group_id FK from tractors (use group_tractor pivot only)
        Schema::table('tractors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });

        // Add a role column to group_user so we can distinguish TPS assignments
        Schema::table('group_user', function (Blueprint $table) {
            $table->string('role')->default('tps')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('tractors', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('device_id')
                ->constrained('tractor_groups')->nullOnDelete();
        });

        Schema::create('group_device', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractor_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tractor_group_id', 'device_id']);
        });
    }
};
