<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biblioteca', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->longText('descripcion'); // Para contenido HTML rico
            $table->foreignId('padre_id')->nullable()->constrained('biblioteca')->onDelete('cascade');
            $table->boolean('is_premium')->default(false);
            $table->integer('orden')->default(0); // Para ordenar elementos
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['padre_id', 'orden']);
            $table->index('is_premium');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biblioteca');
    }
};