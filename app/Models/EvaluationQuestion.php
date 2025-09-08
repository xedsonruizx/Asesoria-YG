<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    use HasFactory;

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
    ];

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
}