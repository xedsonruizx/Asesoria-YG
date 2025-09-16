<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

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
        'triggered_multas', // Agregar campo para almacenar multas activadas
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
        'triggered_multas' => 'array', // Cast para multas activadas
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
            'triggered_multas' => $this->triggered_multas ?? [], // Incluir multas en el reporte
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

    /**
     * Evalúa las condiciones de multas basándose en las respuestas de la evaluación
     */
    public function evaluateTriggeredMultas(): array
    {
        $triggeredMultas = [];
        
        // Obtener todas las respuestas con sus preguntas y multas asociadas
        $answersWithMultas = $this->answers()
            ->with(['question.multas' => function($query) {
                $query->where('evaluation_question_multa.is_active', true);
            }])
            ->get();
        
        foreach ($answersWithMultas as $answer) {
            $question = $answer->question;
            
            if (!$question || $question->multas->isEmpty()) {
                continue;
            }
            
            foreach ($question->multas as $multa) {
                $pivot = $multa->pivot;
                $triggerCondition = $pivot->trigger_condition;
                $triggerValue = $pivot->trigger_value;
                $answerValue = $answer->answer_value;
                
                // Normalizar el valor de la respuesta
                if (is_string($answerValue) && json_decode($answerValue) !== null) {
                    $answerValue = json_decode($answerValue, true);
                }
                $isTriggered = $this->evaluateMultaCondition($triggerCondition, $triggerValue, $answerValue, $question);
                
                if ($isTriggered) {
                    $triggeredMultas[] = [
                        'multa_id' => $multa->id,
                        'multa_name' => $multa->name,
                        'multa_description' => $multa->description,
                        'question_id' => $question->id,
                        'question_text' => $question->question_text,
                        'answer_value' => $answerValue,
                        'trigger_condition' => $triggerCondition,
                        'trigger_value' => $triggerValue,
                        'category_name' => $question->category->name ?? 'Sin categoría'
                    ];
                }
            }
        }
        
        // Guardar las multas activadas en la evaluación
        $this->update(['triggered_multas' => $triggeredMultas]);
        
        return $triggeredMultas;
    }
    
    /**
     * Evalúa una condición específica de multa
     */
    private function evaluateMultaCondition(string $condition, ?string $triggerValue, $answerValue, $question): bool
    {
        // Log para debugging
        Log::info('evaluateMultaCondition', [
            'question_id' => $question->id,
            'question_type' => $question->question_type,
            'condition' => $condition,
            'trigger_value' => $triggerValue,
            'answer_value' => $answerValue,
            'answer_type' => gettype($answerValue)
        ]);

        switch ($condition) {
            case 'equals':
                $result = $this->compareValues($answerValue, $triggerValue, '==', $question);
                Log::info('equals condition result', ['result' => $result]);
                return $result;
                
            case 'not_equals':
                return $this->compareValues($answerValue, $triggerValue, '!=', $question);
                
            case 'contains':
                if (is_array($answerValue)) {
                    return in_array($triggerValue, $answerValue);
                }
                return str_contains((string)$answerValue, (string)$triggerValue);
                
            case 'greater_than':
                return is_numeric($answerValue) && is_numeric($triggerValue) && 
                       (float)$answerValue > (float)$triggerValue;
                
            case 'less_than':
                return is_numeric($answerValue) && is_numeric($triggerValue) && 
                       (float)$answerValue < (float)$triggerValue;
                
            case 'is_empty':
                return empty($answerValue) || 
                       (is_array($answerValue) && empty(array_filter($answerValue))) ||
                       (is_string($answerValue) && trim($answerValue) === '');
                
            case 'is_not_empty':
                return !empty($answerValue) && 
                       !(is_array($answerValue) && empty(array_filter($answerValue))) &&
                       !(is_string($answerValue) && trim($answerValue) === '');
                
            case 'always':
            default:
                return true;
        }
    }
    
    /**
     * Compara dos valores según el operador especificado
     */
    private function compareValues($value1, $value2, string $operator, $question = null): bool
    {
        // Normalizar valores nulos o vacíos
        if ($value1 === null || $value1 === '') {
            $value1 = null;
        }
        if ($value2 === null || $value2 === '') {
            $value2 = null;
        }
    
        $questionType = $question ? $question->question_type : 'unknown';
    
        // Manejo específico por tipo de pregunta
        switch ($questionType) {
            case 'yes_no':
                // Para preguntas yes_no, convertir a boolean
                if (is_string($value1) && in_array($value1, ['0', '1', 'true', 'false'])) {
                    $value1 = filter_var($value1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
                if (is_string($value2) && in_array($value2, ['0', '1', 'true', 'false'])) {
                    $value2 = filter_var($value2, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
                break;
    
            case 'number':
                // Para preguntas numéricas, asegurar conversión a números
                if (is_numeric($value1) || is_string($value1)) {
                    $value1 = (float)$value1;
                }
                if (is_numeric($value2) || is_string($value2)) {
                    $value2 = (float)$value2;
                }
                break;
    
            case 'select':
            case 'radio':
                // Para select y radio, mantener como string para comparación exacta
                $value1 = (string)$value1;
                $value2 = (string)$value2;
                break;
    
            case 'checkbox':
                // Para checkbox, manejar arrays
                if (is_array($value1)) {
                    $value1 = json_encode($value1);
                }
                if (is_array($value2)) {
                    $value2 = json_encode($value2);
                }
                // Si no son arrays, convertir a string
                if (!is_string($value1)) {
                    $value1 = (string)$value1;
                }
                if (!is_string($value2)) {
                    $value2 = (string)$value2;
                }
                break;
    
            case 'text':
            case 'textarea':
            case 'long_text':
                // Para campos de texto, mantener como string
                $value1 = (string)$value1;
                $value2 = (string)$value2;
                break;
    
            default:
                // Para tipos desconocidos, intentar conversión inteligente
                if (is_numeric($value1) && is_numeric($value2)) {
                    $value1 = (float)$value1;
                    $value2 = (float)$value2;
                } else {
                    // Convertir a string como fallback
                    if (is_array($value1)) {
                        $value1 = json_encode($value1);
                    } else {
                        $value1 = (string)$value1;
                    }
                    if (is_array($value2)) {
                        $value2 = json_encode($value2);
                    } else {
                        $value2 = (string)$value2;
                    }
                }
                break;
        }
    
        // Log para debugging
        Log::info('compareValues', [
            'value1' => $value1,
            'value2' => $value2,
            'value1_type' => gettype($value1),
            'value2_type' => gettype($value2),
            'operator' => $operator,
            'question_type' => $questionType
        ]);
        
        switch ($operator) {
            case '==':
            case 'equals':
                $result = $value1 == $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case '!=':
            case 'not_equals':
                $result = $value1 != $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case 'contains':
                $result = is_string($value1) && is_string($value2) && strpos($value1, $value2) !== false;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case 'not_contains':
                $result = !is_string($value1) || !is_string($value2) || strpos($value1, $value2) === false;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case '>':
            case 'greater_than':
                $result = is_numeric($value1) && is_numeric($value2) && $value1 > $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case '<':
            case 'less_than':
                $result = is_numeric($value1) && is_numeric($value2) && $value1 < $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case '>=':
            case 'greater_than_or_equal':
                $result = is_numeric($value1) && is_numeric($value2) && $value1 >= $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            case '<=':
            case 'less_than_or_equal':
                $result = is_numeric($value1) && is_numeric($value2) && $value1 <= $value2;
                Log::info('Comparison result', ['result' => $result]);
                return $result;
            default:
                Log::warning('Unknown comparison operator', ['operator' => $operator]);
                return false;
        }
    }
}