<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->default(null)->change();
        });

        // Convert existing integer values to string labels
        DB::table('users')->where('gender', '0')->update(['gender' => 'male']);
        DB::table('users')->where('gender', '1')->update(['gender' => 'female']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert string labels back to integers
        DB::table('users')->where('gender', 'male')->update(['gender' => '0']);
        DB::table('users')->where('gender', 'female')->update(['gender' => '1']);

        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('gender')->default(0)->comment('0=male,1=female')->change();
        });
    }
};
