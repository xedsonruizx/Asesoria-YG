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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index('status');
            $table->index('is_premium');
            $table->index('is_active');
            $table->index('published_at');
            $table->index('author_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
