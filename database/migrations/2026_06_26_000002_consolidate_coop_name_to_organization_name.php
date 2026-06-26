<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy existing coop_name values to organization_name (where organization_name is null)
        DB::statement('UPDATE users SET organization_name = coop_name WHERE organization_name IS NULL AND coop_name IS NOT NULL');

        // Drop the old column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('coop_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('coop_name')->nullable()->after('name');
        });

        DB::statement('UPDATE users SET coop_name = organization_name WHERE coop_name IS NULL AND organization_name IS NOT NULL');
    }
};
