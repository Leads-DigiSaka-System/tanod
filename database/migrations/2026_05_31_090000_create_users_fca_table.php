<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_fca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('organization_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->decimal('parking_latitude', 10, 7);
            $table->decimal('parking_longitude', 10, 7);
            $table->string('province');
            $table->string('city_town');
            $table->string('barangay');
            $table->date('date_received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_fca');
    }
};
