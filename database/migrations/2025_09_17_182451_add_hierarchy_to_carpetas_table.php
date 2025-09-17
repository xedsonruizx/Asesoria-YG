<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpetas', function (Blueprint $table) {
            // Solo agregar índices adicionales si no existen
            if (!Schema::hasIndex('carpetas', 'carpetas_padre_id_orden_index')) {
                $table->index(['padre_id', 'orden']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->dropIndex(['padre_id', 'orden']);
        });
    }
};