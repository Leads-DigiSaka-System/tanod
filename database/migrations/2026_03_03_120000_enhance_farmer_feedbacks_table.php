<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_feedbacks', function (Blueprint $table) {
            $table->string('category')->nullable()->after('status')->comment('service, tractor, operator, general');
            $table->text('admin_response')->nullable()->after('conclusion');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['category', 'admin_response', 'reviewed_at']);
        });
    }
};
