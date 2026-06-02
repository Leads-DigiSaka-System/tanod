<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        $usersWithMissingEmail = DB::table('users')
            ->whereNull('email')
            ->pluck('id');

        foreach ($usersWithMissingEmail as $userId) {
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'email' => sprintf('restored-user-%d@tanod.local', $userId),
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 191)->nullable(false)->change();
        });
    }
};
