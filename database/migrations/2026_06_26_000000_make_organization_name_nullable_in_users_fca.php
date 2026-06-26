<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_fca', function (Blueprint $table) {
            $table->string('organization_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users_fca', function (Blueprint $table) {
            $table->string('organization_name')->nullable(false)->change();
        });
    }
};
