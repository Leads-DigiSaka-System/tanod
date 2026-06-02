<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('philippine_regions', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 20)->index();
            $table->string('region_description');
            $table->string('region_number', 50)->nullable();
            $table->string('region_code', 20)->index();
            $table->timestamps();
        });

        Schema::create('philippine_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 20)->index();
            $table->string('province_description');
            $table->string('region_code', 20)->index();
            $table->string('province_code', 20)->index();
            $table->timestamps();
        });

        Schema::create('philippine_cities', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 20)->index();
            $table->string('city_municipality_description');
            $table->string('region_code', 20)->index();
            $table->string('province_code', 20)->index();
            $table->string('city_municipality_code', 20)->index();
            $table->timestamps();
        });

        Schema::create('philippine_barangays', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 20)->index();
            $table->string('barangay_description');
            $table->string('region_code', 20)->index();
            $table->string('province_code', 20)->index();
            $table->string('city_municipality_code', 20)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('philippine_barangays');
        Schema::dropIfExists('philippine_cities');
        Schema::dropIfExists('philippine_provinces');
        Schema::dropIfExists('philippine_regions');
    }
};
