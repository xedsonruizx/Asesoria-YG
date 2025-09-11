<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->json('answer_value'); // Valor de la respuesta (puede ser string, array, etc.)
            $table->integer('points_earned')->default(0);
            $table->decimal('completion_time', 8, 2)->nullable(); // Tiempo en segundos para responder
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Datos adicionales (IP, user agent, etc.)
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['evaluation_id', 'question_id']);
            $table->index(['evaluation_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};