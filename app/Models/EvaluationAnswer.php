<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EvaluationAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'evaluation_id',
        'question_id',
        'answer_value',
        'points_earned',
        'is_active',
    ];

    protected $casts = [
        'answer_value' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    /**
     * Método personalizado para crear o actualizar respuesta de forma segura
     */
    public static function safeUpsert(int $evaluationId, int $questionId, $answerValue): self
    {
        // Usar firstOrNew para obtener el registro existente o crear uno nuevo
        $answer = self::firstOrNew([
            'evaluation_id' => $evaluationId,
            'question_id' => $questionId,
        ]);
        
        // Actualizar el valor de la respuesta
        $answer->answer_value = $answerValue;
        
        // Intentar guardar con manejo de duplicados
        try {
            $answer->save();
            return $answer;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Si falla por duplicado, buscar el registro existente
            $existingAnswer = self::where([
                'evaluation_id' => $evaluationId,
                'question_id' => $questionId,
            ])->first();
            
            if ($existingAnswer) {
                $existingAnswer->update(['answer_value' => $answerValue]);
                return $existingAnswer;
            }
            
            // Si no existe, relanzar la excepción
            throw $e;
        }
    }

    public function calculatePoints(): void
    {
        $question = $this->question;
        $answer = $this->answer_value;
        $points = 0;

        // Manejar diferentes tipos de preguntas
        switch ($question->question_type) {
            case 'yes_no':
                $points = $answer === true ? $question->points : 0;
                break;
                
            case 'select':
            case 'radio':
                if (!empty($answer) && is_array($question->options)) {
                    // Buscar la opción seleccionada y obtener sus puntos
                    foreach ($question->options as $option) {
                        if (is_array($option) && isset($option['text']) && $option['text'] === $answer) {
                            $points = $option['points'] ?? 0;
                            break;
                        } elseif (is_string($option) && $option === $answer) {
                            // Compatibilidad con formato anterior
                            $points = $question->points;
                            break;
                        }
                    }
                }
                break;
                
            case 'checkbox':
                if (is_array($answer) && is_array($question->options)) {
                    // Sumar puntos de todas las opciones seleccionadas
                    foreach ($answer as $selectedOption) {
                        foreach ($question->options as $option) {
                            if (is_array($option) && isset($option['text']) && $option['text'] === $selectedOption) {
                                $points += $option['points'] ?? 0;
                                break;
                            } elseif (is_string($option) && $option === $selectedOption) {
                                // Compatibilidad con formato anterior
                                $points += $question->points;
                                break;
                            }
                        }
                    }
                }
                break;
                
            case 'text':
            case 'textarea':
            case 'number':
                // Para preguntas de texto/número, asignar puntos si hay respuesta
                $points = !empty($answer) ? $question->points : 0;
                break;
        }

        $this->update(['points_earned' => $points]);
    }
}