<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_question_multa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('multa_id')->constrained()->onDelete('cascade');
            $table->string('trigger_condition')->default('always'); // always, on_fail, on_specific_answer, on_low_score
            $table->string('trigger_value')->nullable(); // valor específico para la condición
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['evaluation_question_id', 'multa_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_question_multa');
    }
};