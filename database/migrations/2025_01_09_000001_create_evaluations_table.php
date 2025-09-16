<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('category_scores')->nullable(); // Puntajes por categoría {"rrhh": 85, "legal": 92}
            $table->json('category_progress')->nullable(); // Progreso por categoría {"rrhh": 100, "legal": 80}
            $table->integer('total_score')->default(0);
            $table->integer('total_progress')->default(0);
            $table->enum('status', ['draft', 'in_progress', 'completed', 'expired'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Para evaluaciones con tiempo límite
            $table->json('metadata')->nullable(); // Datos adicionales del reporte
            $table->json('triggered_multas')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};