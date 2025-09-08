<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('evaluation_categories')->onDelete('cascade');
            $table->text('question_text');
            $table->enum('question_type', ['text', 'textarea', 'select', 'number', 'checkbox', 'yes_no', 'radio']);
            $table->json('options')->nullable(); // Para select, radio, checkbox
            $table->string('placeholder')->nullable();
            $table->integer('min_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->json('show_condition')->nullable(); // Condiciones para mostrar la pregunta
            $table->json('validation_rules')->nullable(); // Reglas de validación
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_questions');
    }
};