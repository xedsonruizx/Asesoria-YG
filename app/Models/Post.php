<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content', 
        'excerpt',
        'slug',
        'meta_description',
        'status',
        'is_premium',
        'image_path',
        'file_path',
        'author_id',
        'published_at',
        'is_active'
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Scopes para filtrar por estado
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->whereHas('tags', function ($q) use ($category) {
            $q->where('slug', $category);
        });
    }

    // Accessor para obtener la URL de la imagen
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    // Accessor para obtener la URL del archivo
    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    // Relación con TagCategory
    public function tags()
    {
        return $this->belongsToMany(TagCategory::class, 'post_tag_category', 'post_id', 'tag_category_id')
                    ->withTimestamps();
    }

    // Alias para mantener consistencia con el nombre tagCategories
    public function tagCategories()
    {
        return $this->tags();
    }

    // Scope para filtrar por tags
    public function scopeWithTags($query, $tagIds)
    {
        if (!empty($tagIds)) {
            return $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags_category.id', $tagIds);
            });
        }
        return $query;
    }
}