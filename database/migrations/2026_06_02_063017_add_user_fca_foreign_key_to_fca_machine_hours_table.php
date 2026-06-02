<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->shouldManageForeignKey() || $this->hasForeignKey()) {
            return;
        }

        Schema::table('fca_machine_hours', function (Blueprint $table) {
            $table->foreign('user_fca_id', 'fca_machine_hours_user_fca_id_foreign')
                ->references('id')
                ->on('users_fca')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! $this->shouldManageForeignKey() || ! $this->hasForeignKey()) {
            return;
        }

        Schema::table('fca_machine_hours', function (Blueprint $table) {
            $table->dropForeign('fca_machine_hours_user_fca_id_foreign');
        });
    }

    private function shouldManageForeignKey(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite'
            && Schema::hasTable('fca_machine_hours')
            && Schema::hasTable('users_fca');
    }

    private function hasForeignKey(): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'fca_machine_hours')
            ->where('CONSTRAINT_NAME', 'fca_machine_hours_user_fca_id_foreign')
            ->where('COLUMN_NAME', 'user_fca_id')
            ->where('REFERENCED_TABLE_NAME', 'users_fca')
            ->exists();
    }
};
