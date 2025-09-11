<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_tag_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_category_id')->constrained('tags_category')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['post_id', 'tag_category_id']);
            $table->index(['post_id']);
            $table->index(['tag_category_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tag_category');
    }
};