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
        Schema::create('fca_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_fca_id')->constrained('users_fca')->cascadeOnDelete();
            $table->unsignedTinyInteger('question_number');
            $table->unsignedInteger('entry_order')->default(0);
            $table->text('answer_text')->nullable();
            $table->boolean('boolean_answer')->nullable();
            $table->timestamps();

            $table->index(['user_fca_id', 'question_number', 'entry_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fca_survey_answers');
    }
};
