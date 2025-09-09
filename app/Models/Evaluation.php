<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'category_scores' => 'array',
        'category_progress' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
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

        $this->update([
            'category_scores' => $categoryScores,
            'category_progress' => $categoryProgress,
            'total_score' => $totalScore,
            'total_progress' => $avgProgress,
            'status' => $avgProgress === 100 ? 'completed' : 'in_progress',
            'completed_at' => $avgProgress === 100 ? now() : null,
        ]);
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
    
        $maxTotalScore = 0; // Agregar esta línea
        
        foreach ($categories as $category) {
            $categoryScore = $this->category_scores[$category->slug] ?? 0;
            $categoryProgress = $this->category_progress[$category->slug] ?? 0;
            $maxScore = $category->max_score;
            $percentage = $maxScore > 0 ? round(($categoryScore / $maxScore) * 100) : 0;
            
            $maxTotalScore += $maxScore; // Agregar esta línea
    
            $report['categories'][] = [
                'name' => $category->name,
                'slug' => $category->slug,
                'score' => $categoryScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'progress' => $categoryProgress,
                'color' => $category->color,
                'status' => $this->getCategoryStatus($percentage),
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
    
        // Agregar los campos faltantes
        $report['max_total_score'] = $maxTotalScore;
        $report['total_percentage'] = $maxTotalScore > 0 ? round(($this->total_score / $maxTotalScore) * 100, 1) : 0;

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