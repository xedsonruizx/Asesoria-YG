<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // RRHH, Legal, Financiero, etc.
            $table->string('slug')->unique(); // rrhh, legal, financiero
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Color hex para UI
            $table->string('icon')->nullable(); // Icono para UI
            $table->integer('max_score')->default(100); // Puntaje máximo de la categoría
            $table->integer('order')->default(0); // Orden de visualización
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_categories');
    }
};