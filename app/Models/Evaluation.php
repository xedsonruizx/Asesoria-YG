<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_scores',
        'category_progress',
        'total_score',
        'total_progress',
        'status',
        'started_at',
        'completed_at',
        'expires_at',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'category_scores' => 'array',
        'category_progress' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }

    public function calculateScoresByCategory(): void
    {
        $categories = EvaluationCategory::active()->get();
        $categoryScores = [];
        $categoryProgress = [];
        $totalScore = 0;
        $totalProgress = 0;

        foreach ($categories as $category) {
            $categoryAnswers = $this->answers()
                ->whereHas('question', fn($q) => $q->where('category_id', $category->id))
                ->get();

            $categoryScore = $categoryAnswers->sum('points_earned');
            $totalQuestions = $category->questions()->count();
            $answeredQuestions = $categoryAnswers->count();
            
            $progress = $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0;
            
            $categoryScores[$category->slug] = $categoryScore;
            $categoryProgress[$category->slug] = $progress;
            
            $totalScore += $categoryScore;
            $totalProgress += $progress;
        }

        $avgProgress = count($categories) > 0 ? round($totalProgress / count($categories)) : 0;

        // Preparar los datos para actualizar
        $updateData = [
            'category_scores' => $categoryScores,
            'category_progress' => $categoryProgress,
            'total_score' => $totalScore,
            'total_progress' => $avgProgress,
        ];

        // Solo actualizar el status si la evaluación no está completada
        if ($this->status !== 'completed') {
            $updateData['status'] = $avgProgress === 100 ? 'completed' : 'in_progress';
            $updateData['completed_at'] = $avgProgress === 100 ? now() : null;
        }

        $this->update($updateData);
    }

    public function generateReport(): array
    {
        $categories = EvaluationCategory::active()->ordered()->get();
        $report = [
            'evaluation_id' => $this->id,
            'user' => $this->user->name,
            'completed_at' => $this->completed_at,
            'total_score' => $this->total_score,
            'total_progress' => $this->total_progress,
            'categories' => [],
            'recommendations' => [],
        ];
    
        $maxTotalScore = 0;
        
        foreach ($categories as $category) {
            // Obtener solo las respuestas de esta categoría
            $categoryAnswers = $this->answers()
                ->whereHas('question', fn($q) => $q->where('category_id', $category->id))
                ->with('question')
                ->get();
            
            // Calcular puntos obtenidos y puntos totales posibles solo de preguntas respondidas
            $obtainedPoints = $categoryAnswers->sum('points_earned');
            $totalPossiblePoints = $categoryAnswers->sum(function($answer) {
                return $answer->question->points ?? 0;
            });
            
            // Calcular porcentaje basado solo en preguntas con respuesta
            $percentage = $totalPossiblePoints > 0 ? round(($obtainedPoints / $totalPossiblePoints) * 100, 1) : 0;
            
            $categoryScore = $this->category_scores[$category->slug] ?? 0;
            $categoryProgress = $this->category_progress[$category->slug] ?? 0;
            
            $maxTotalScore += $totalPossiblePoints;
    
            $report['categories'][] = [
                'name' => $category->name,
                'slug' => $category->slug,
                'score' => $categoryScore,
                'max_score' => $totalPossiblePoints,
                'percentage' => $percentage,
                'progress' => $categoryProgress,
                'color' => $category->color,
                'status' => $this->getCategoryStatus($percentage),
                'obtained_points' => $obtainedPoints,
                'total_possible_points' => $totalPossiblePoints,
                'answered_questions' => $categoryAnswers->count()
            ];
    
            // Generar recomendaciones basadas en el puntaje
            if ($percentage < 60) {
                $report['recommendations'][] = [
                    'category' => $category->name,
                    'type' => 'critical',
                    'message' => "Se requiere atención inmediata en {$category->name}",
                ];
            } elseif ($percentage < 80) {
                $report['recommendations'][] = [
                    'category' => $category->name,
                    'type' => 'warning',
                    'message' => "Se recomienda mejorar en {$category->name}",
                ];
            }
        }
    
        // Calcular porcentaje total basado en todas las preguntas respondidas
        $totalObtainedPoints = $this->answers()->sum('points_earned');
        $totalPossiblePointsAll = $this->answers()->with('question')->get()->sum(function($answer) {
            return $answer->question->points ?? 0;
        });
        
        $report['max_total_score'] = $totalPossiblePointsAll;
        $report['total_percentage'] = $totalPossiblePointsAll > 0 ? round(($totalObtainedPoints / $totalPossiblePointsAll) * 100, 1) : 0;
        $report['total_obtained_points'] = $totalObtainedPoints;
        $report['total_possible_points'] = $totalPossiblePointsAll;
    
        return $report;
    }

    private function getCategoryStatus(int $percentage): string
    {
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 80) return 'good';
        if ($percentage >= 60) return 'fair';
        return 'poor';
    }
}