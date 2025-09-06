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
            $table->text('content', 60000);
            $table->text('excerpt')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->text('tags')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_premium')->default(false);
            $table->string('image_path')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('status');
            $table->index('category_id');
            $table->index('author_id');
            $table->index('published_at');
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
