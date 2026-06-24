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
        Schema::table('province_support_contact', function (Blueprint $table) {
            if (!Schema::hasColumn('province_support_contact', 'user_id')) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('province_support_contact', 'province_code')) {
                $table->string('province_code', 20);
            }
            if (!Schema::hasColumn('province_support_contact', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('province_support_contact', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'province_code']);
        });
    }
};
