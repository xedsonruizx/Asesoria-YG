<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biblioteca', function (Blueprint $table) {
            $table->foreignId('carpeta_id')->nullable()->after('padre_id')->constrained('carpetas')->onDelete('set null');
            $table->index('carpeta_id');
        });
    }

    public function down(): void
    {
        Schema::table('biblioteca', function (Blueprint $table) {
            $table->dropForeign(['carpeta_id']);
            $table->dropIndex(['carpeta_id']);
            $table->dropColumn('carpeta_id');
        });
    }
};