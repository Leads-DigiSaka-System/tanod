<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'down_payment')) {
                $table->decimal('down_payment', 12, 2)->nullable()->after('service_charge');
            }
            if (! Schema::hasColumn('tickets', 'installments')) {
                $table->unsignedTinyInteger('installments')->nullable()->after('down_payment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['down_payment', 'installments']);
        });
    }
};
