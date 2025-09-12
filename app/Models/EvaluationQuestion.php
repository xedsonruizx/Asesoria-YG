<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'question_text',
        'question_type',
        'options',
        'placeholder',
        'min_value',
        'max_value',
        'points',
        'order',
        'show_condition',
        'validation_rules',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'show_condition' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Accessor para asegurar que options siempre sea un array
     */
    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        
        return is_array($value) ? $value : [];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EvaluationCategory::class, 'category_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope para incluir preguntas eliminadas (para reportes)
     */
    public function scopeWithDeleted($query)
    {
        return $query->withTrashed();
    }

    /**
     * Scope para obtener solo preguntas eliminadas
     */
    public function scopeOnlyDeleted($query)
    {
        return $query->onlyTrashed();
    }

    /**
     * Relación con multas
     */
    public function multas()
    {
        return $this->belongsToMany(Multa::class, 'evaluation_question_multa', 'evaluation_question_id', 'multa_id')
                    ->withPivot('trigger_condition', 'trigger_value', 'is_active')
                    ->withTimestamps();
    }
}