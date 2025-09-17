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
        Schema::create('carpetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icono')->default('folder');
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            
            // Soporte para jerarquía (subcarpetas)
            $table->foreignId('padre_id')->nullable()->constrained('carpetas')->onDelete('cascade');
            $table->integer('nivel')->default(0);
            $table->string('ruta_completa')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('activa');
            $table->index('nivel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpetas');
    }
};
