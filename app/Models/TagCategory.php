<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagCategory extends Model
{
    use HasFactory;

    protected $table = 'tags_category';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relación many-to-many con posts
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag_category', 'tag_category_id', 'post_id')
                    ->withTimestamps();
    }

    // Scope para tags activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para buscar por nombre
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
    }

    // Generar slug automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // Contar posts asociados
    public function getPostsCountAttribute()
    {
        return $this->posts()->count();
    }
}