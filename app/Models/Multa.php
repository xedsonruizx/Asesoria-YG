<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Multa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'multas';

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope para multas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para multas inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Accessor para obtener la URL del archivo
     */
    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Accessor para obtener el estado como texto
     */
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Activo' : 'Inactivo';
    }

    /**
     * Relación con preguntas de evaluación (para futuro uso)
     * Cuando se implemente la asignación de multas a preguntas
     */
    public function evaluationQuestions()
    {
        return $this->belongsToMany(EvaluationQuestion::class, 'evaluation_question_multa', 'multa_id', 'evaluation_question_id')
                    ->withPivot('trigger_condition', 'trigger_value', 'is_active')
                    ->withTimestamps();
    }
}