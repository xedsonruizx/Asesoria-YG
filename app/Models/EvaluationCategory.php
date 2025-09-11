<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    // Agregar nueva relación para contar todas las preguntas
    public function allQuestions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}